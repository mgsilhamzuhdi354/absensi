<?php
/**
 * Script untuk restore SEMUA data dari backup JSON
 * Jalankan: php restore_all_data.php
 */

// Load backup file
$backupFile = __DIR__ . '/../backup_2026-02-04_063350.json';

if (!file_exists($backupFile)) {
    die("Error: Backup file tidak ditemukan di: $backupFile\n");
}

echo "=================================================\n";
echo "  RESTORE SEMUA DATA DARI BACKUP\n";
echo "=================================================\n\n";

echo "Membaca file backup...\n";
$jsonContent = file_get_contents($backupFile);
$backupData = json_decode($jsonContent, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("Error: Gagal parse JSON - " . json_last_error_msg() . "\n");
}

// Database connection
$host = '127.0.0.1';
$dbname = 'absensi_laravel';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Koneksi database berhasil!\n\n";
} catch (PDOException $e) {
    die("Error koneksi database: " . $e->getMessage() . "\n");
}

// Define the mapping of JSON keys to database table names
// Order matters - parent tables first, then child tables
$tableMapping = [
    'lokasis' => 'lokasis',
    'shifts' => 'shifts',
    'users' => 'users',
    'mapping_shifts' => 'mapping_shifts',
    'cutis' => 'cutis',
    'kasbons' => 'kasbons',
    'reimbursements' => 'reimbursements',
    'payrolls' => 'payrolls',
    'beritas' => 'beritas',
    'jenis_kinerjas' => 'jenis_kinerjas',
    'laporan_kinerjas' => 'laporan_kinerjas',
    'target_kinerjas' => 'target_kinerjas',
    'target_kinerja_teams' => 'target_kinerja_teams',
];

// Tables that have foreign key constraints and may need special handling
$tablesWithFK = ['mapping_shifts', 'cutis', 'kasbons', 'reimbursements', 'payrolls', 'laporan_kinerjas', 'target_kinerjas', 'target_kinerja_teams'];

// Disable foreign key checks temporarily
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

$totalSuccess = 0;
$totalError = 0;

foreach ($tableMapping as $jsonKey => $tableName) {
    if (!isset($backupData[$jsonKey])) {
        echo "⚠️  Data '$jsonKey' tidak ditemukan dalam backup, dilewati.\n";
        continue;
    }

    $records = $backupData[$jsonKey];

    // Skip if not an array or is empty
    if (!is_array($records)) {
        echo "⚠️  Data '$jsonKey' bukan array, dilewati.\n";
        continue;
    }

    $recordCount = count($records);
    echo "\n" . str_repeat("-", 50) . "\n";
    echo "📊 Tabel: $tableName ($recordCount records)\n";
    echo str_repeat("-", 50) . "\n";

    if ($recordCount === 0) {
        echo "   (kosong, tidak ada yang diimport)\n";
        continue;
    }

    // Get table columns
    try {
        $stmt = $pdo->query("DESCRIBE $tableName");
        $tableColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException $e) {
        echo "❌ Error: Tabel '$tableName' tidak ditemukan di database!\n";
        continue;
    }

    $successCount = 0;
    $errorCount = 0;

    foreach ($records as $record) {
        try {
            // Filter only columns that exist in the table
            $filteredRecord = array_filter($record, function ($key) use ($tableColumns) {
                return in_array($key, $tableColumns);
            }, ARRAY_FILTER_USE_KEY);

            if (empty($filteredRecord)) {
                continue;
            }

            // Build INSERT ... ON DUPLICATE KEY UPDATE query
            $columns = array_keys($filteredRecord);
            $placeholders = array_map(fn($col) => ":$col", $columns);
            $updatePairs = array_map(fn($col) => "$col = VALUES($col)", $columns);

            $sql = "INSERT INTO $tableName (" . implode(", ", $columns) . ") 
                    VALUES (" . implode(", ", $placeholders) . ")
                    ON DUPLICATE KEY UPDATE " . implode(", ", $updatePairs);

            $stmt = $pdo->prepare($sql);

            foreach ($filteredRecord as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }

            $stmt->execute();
            $successCount++;

        } catch (PDOException $e) {
            $errorCount++;
            // Only show first few errors per table
            if ($errorCount <= 3) {
                echo "   ❌ Error: " . substr($e->getMessage(), 0, 80) . "...\n";
            }
        }
    }

    echo "   ✅ Berhasil: $successCount | ❌ Gagal: $errorCount\n";
    $totalSuccess += $successCount;
    $totalError += $errorCount;
}

// Re-enable foreign key checks
$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

echo "\n" . str_repeat("=", 50) . "\n";
echo "  RINGKASAN RESTORE\n";
echo str_repeat("=", 50) . "\n";
echo "Total Records Berhasil: $totalSuccess\n";
echo "Total Records Gagal: $totalError\n";
echo str_repeat("=", 50) . "\n";
echo "\n✅ RESTORE SELESAI!\n";
