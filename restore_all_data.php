<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

const CONFIRM_UPSERT = 'RESTORE_DATA';
const CONFIRM_REPLACE = 'REPLACE_ALL_RESTORE_DATA';

$restoreTables = [
    'lokasis',
    'shifts',
    'jenis_kinerjas',
    'users',
    'mapping_shifts',
    'cutis',
    'kasbons',
    'reimbursements',
    'payrolls',
    'beritas',
    'laporan_kinerjas',
    'target_kinerjas',
    'target_kinerja_teams',
];

$deleteOrder = array_reverse($restoreTables);

exit(main($argv, $restoreTables, $deleteOrder));

function main(array $argv, array $restoreTables, array $deleteOrder): int
{
    $options = parseArguments($argv);

    if ($options['help']) {
        printUsage();
        return 0;
    }

    if ($options['backup_file'] === null) {
        printError('Backup file wajib diisi.');
        printUsage();
        return 1;
    }

    if (!in_array($options['mode'], ['upsert', 'replace'], true)) {
        printError("Mode harus 'upsert' atau 'replace'.");
        return 1;
    }

    if ($options['execute'] && !hasValidConfirmation($options)) {
        printError('Konfirmasi tidak valid. Restore dibatalkan sebelum menyentuh database.');
        printConfirmationHelp($options['mode']);
        return 1;
    }

    $backupFile = resolveBackupPath($options['backup_file']);
    $backupData = loadBackupData($backupFile, $restoreTables);

    if ($backupData === null) {
        return 1;
    }

    require __DIR__ . '/vendor/autoload.php';
    $app = require_once __DIR__ . '/bootstrap/app.php';
    $app->make(Kernel::class)->bootstrap();

    $appEnv = (string) config('app.env');
    $connectionName = DB::getDefaultConnection();
    $databaseName = (string) config("database.connections.$connectionName.database");

    if ($appEnv === 'production' && !$options['allow_production']) {
        printError('APP_ENV=production terdeteksi. Restore diblokir.');
        echo "Tambahkan --allow-production hanya jika benar-benar sengaja menjalankan di produksi.\n";
        return 1;
    }

    printHeader($backupFile, $options, $appEnv, $connectionName, $databaseName);

    try {
        $validationErrors = validateDatabaseSchema($restoreTables);
        if ($validationErrors !== []) {
            foreach ($validationErrors as $error) {
                printError($error);
            }
            return 1;
        }
    } catch (Throwable $e) {
        printError('Tidak bisa konek atau validasi database: ' . $e->getMessage());
        return 1;
    }

    printBackupSummary($backupData, $restoreTables);

    if (!$options['execute']) {
        echo "\nDRY RUN selesai. Tidak ada data database yang diubah.\n";
        printConfirmationHelp($options['mode']);
        return 0;
    }

    try {
        if ($options['mode'] === 'replace') {
            replaceData($backupData, $restoreTables, $deleteOrder);
        } else {
            upsertData($backupData, $restoreTables);
        }
    } catch (Throwable $e) {
        printError('Restore gagal dan transaction di-rollback: ' . $e->getMessage());
        return 1;
    }

    echo "\nRestore selesai dengan sukses.\n";
    return 0;
}

function parseArguments(array $argv): array
{
    $options = [
        'backup_file' => null,
        'mode' => 'upsert',
        'execute' => false,
        'confirm' => null,
        'allow_production' => false,
        'help' => false,
    ];

    foreach (array_slice($argv, 1) as $argument) {
        if ($argument === '--help' || $argument === '-h') {
            $options['help'] = true;
            continue;
        }

        if ($argument === '--execute') {
            $options['execute'] = true;
            continue;
        }

        if ($argument === '--allow-production') {
            $options['allow_production'] = true;
            continue;
        }

        if (strpos($argument, '--mode=') === 0) {
            $options['mode'] = substr($argument, strlen('--mode='));
            continue;
        }

        if (strpos($argument, '--confirm=') === 0) {
            $options['confirm'] = substr($argument, strlen('--confirm='));
            continue;
        }

        if ($options['backup_file'] === null) {
            $options['backup_file'] = $argument;
            continue;
        }

        printError("Argumen tidak dikenal: $argument");
        $options['help'] = true;
        break;
    }

    return $options;
}

function printUsage(): void
{
    echo "Usage:\n";
    echo "  php restore_all_data.php path/to/backup.json [--mode=upsert|replace] [--execute] [--confirm=TEXT]\n\n";
    echo "Default aman:\n";
    echo "  - Tanpa --execute, script hanya validasi dan dry-run.\n";
    echo "  - Mode default adalah upsert: tambah/update data tanpa menghapus data lama.\n";
    echo "  - Mode replace akan menghapus isi tabel target lalu isi ulang dari backup.\n\n";
    echo "Contoh aman:\n";
    echo "  php restore_all_data.php ../backup_2026-02-04_063350.json\n";
    echo "  php restore_all_data.php ../backup_2026-02-04_063350.json --execute --confirm=" . CONFIRM_UPSERT . "\n\n";
    echo "Contoh destructive, hanya jika benar-benar ingin replace total:\n";
    echo "  php restore_all_data.php backup.json --mode=replace --execute --confirm=" . CONFIRM_REPLACE . "\n";
}

function printConfirmationHelp(string $mode): void
{
    echo "\nUntuk menjalankan restore sungguhan:\n";

    if ($mode === 'replace') {
        echo "  php restore_all_data.php backup.json --mode=replace --execute --confirm=" . CONFIRM_REPLACE . "\n";
        echo "PERINGATAN: mode replace menghapus data lama di tabel target.\n";
        return;
    }

    echo "  php restore_all_data.php backup.json --execute --confirm=" . CONFIRM_UPSERT . "\n";
    echo "Mode upsert tidak menghapus data lama yang tidak ada di backup.\n";
}

function hasValidConfirmation(array $options): bool
{
    if ($options['mode'] === 'replace') {
        return $options['confirm'] === CONFIRM_REPLACE;
    }

    return $options['confirm'] === CONFIRM_UPSERT;
}

function resolveBackupPath(string $backupFile): string
{
    if (preg_match('/^[A-Za-z]:[\/\\\\]/', $backupFile) === 1 || strpos($backupFile, DIRECTORY_SEPARATOR) === 0) {
        return $backupFile;
    }

    return getcwd() . DIRECTORY_SEPARATOR . $backupFile;
}

function loadBackupData(string $backupFile, array $restoreTables): ?array
{
    if (!is_file($backupFile)) {
        printError("Backup file tidak ditemukan: $backupFile");
        return null;
    }

    if (!is_readable($backupFile)) {
        printError("Backup file tidak bisa dibaca: $backupFile");
        return null;
    }

    $jsonContent = file_get_contents($backupFile);
    if ($jsonContent === false) {
        printError("Gagal membaca backup file: $backupFile");
        return null;
    }

    $backupData = json_decode($jsonContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        printError('JSON tidak valid: ' . json_last_error_msg());
        return null;
    }

    if (!is_array($backupData)) {
        printError('Struktur backup tidak valid. Root JSON harus object.');
        return null;
    }

    foreach ($restoreTables as $table) {
        if (!array_key_exists($table, $backupData)) {
            printError("Backup tidak berisi key tabel wajib: $table");
            return null;
        }

        if (!is_array($backupData[$table])) {
            printError("Data backup untuk tabel '$table' harus berupa array.");
            return null;
        }

        foreach ($backupData[$table] as $index => $record) {
            if (!is_array($record)) {
                printError("Record ke-$index di tabel '$table' harus berupa object/array.");
                return null;
            }
        }
    }

    return $backupData;
}

function validateDatabaseSchema(array $restoreTables): array
{
    $errors = [];

    foreach ($restoreTables as $table) {
        if (!Schema::hasTable($table)) {
            $errors[] = "Tabel database tidak ditemukan: $table";
        }
    }

    return $errors;
}

function printHeader(string $backupFile, array $options, string $appEnv, string $connectionName, string $databaseName): void
{
    echo str_repeat('=', 70) . "\n";
    echo "SAFE RESTORE DATA\n";
    echo str_repeat('=', 70) . "\n";
    echo "Backup file : $backupFile\n";
    echo "APP_ENV     : $appEnv\n";
    echo "Connection  : $connectionName\n";
    echo "Database    : $databaseName\n";
    echo "Mode        : {$options['mode']}\n";
    echo "Execute     : " . ($options['execute'] ? 'yes' : 'no, dry-run only') . "\n";
    echo str_repeat('=', 70) . "\n";
}

function printBackupSummary(array $backupData, array $restoreTables): void
{
    echo "\nRingkasan backup:\n";
    foreach ($restoreTables as $table) {
        echo '  - ' . str_pad($table, 22) . count($backupData[$table]) . " records\n";
    }
}

function replaceData(array $backupData, array $restoreTables, array $deleteOrder): void
{
    echo "\nMenjalankan replace total dalam transaction...\n";

    DB::beginTransaction();
    DB::statement('SET FOREIGN_KEY_CHECKS=0');

    try {
        foreach ($deleteOrder as $table) {
            DB::table($table)->delete();
            echo "  Cleared $table\n";
        }

        insertRecords($backupData, $restoreTables);

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        DB::commit();
    } catch (Throwable $e) {
        DB::rollBack();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
        throw $e;
    }
}

function upsertData(array $backupData, array $restoreTables): void
{
    echo "\nMenjalankan upsert dalam transaction...\n";

    DB::beginTransaction();

    try {
        insertRecords($backupData, $restoreTables, true);
        DB::commit();
    } catch (Throwable $e) {
        DB::rollBack();
        throw $e;
    }
}

function insertRecords(array $backupData, array $restoreTables, bool $useUpsert = false): void
{
    foreach ($restoreTables as $table) {
        $records = $backupData[$table];
        $columns = Schema::getColumnListing($table);
        $count = 0;

        foreach ($records as $record) {
            $filteredRecord = filterRecordColumns($record, $columns);
            if ($filteredRecord === []) {
                continue;
            }

            if ($useUpsert) {
                upsertRecord($table, $filteredRecord);
            } else {
                DB::table($table)->insert($filteredRecord);
            }

            $count++;
        }

        echo "  Imported $table: $count records\n";
    }
}

function filterRecordColumns(array $record, array $columns): array
{
    return array_intersect_key($record, array_flip($columns));
}

function upsertRecord(string $table, array $record): void
{
    $columns = array_keys($record);
    $quotedColumns = array_map('quoteIdentifier', $columns);
    $placeholders = array_fill(0, count($columns), '?');
    $updates = array_map(static function (string $column): string {
        $quotedColumn = quoteIdentifier($column);
        return "$quotedColumn = VALUES($quotedColumn)";
    }, $columns);

    $sql = 'INSERT INTO ' . quoteIdentifier($table) .
        ' (' . implode(', ', $quotedColumns) . ')' .
        ' VALUES (' . implode(', ', $placeholders) . ')' .
        ' ON DUPLICATE KEY UPDATE ' . implode(', ', $updates);

    DB::statement($sql, array_values($record));
}

function quoteIdentifier(string $identifier): string
{
    return '`' . str_replace('`', '``', $identifier) . '`';
}

function printError(string $message): void
{
    fwrite(STDERR, "[ERROR] $message\n");
}
