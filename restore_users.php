<?php
/**
 * Script untuk restore/update data users dari backup JSON
 * Jalankan: php restore_users.php
 */

// Load backup file
$backupFile = __DIR__ . '/../backup_2026-02-04_063350.json';

if (!file_exists($backupFile)) {
    die("Error: Backup file tidak ditemukan di: $backupFile\n");
}

echo "Membaca file backup...\n";
$jsonContent = file_get_contents($backupFile);
$backupData = json_decode($jsonContent, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    die("Error: Gagal parse JSON - " . json_last_error_msg() . "\n");
}

if (!isset($backupData['users']) || !is_array($backupData['users'])) {
    die("Error: Data 'users' tidak ditemukan dalam backup\n");
}

$users = $backupData['users'];
echo "Ditemukan " . count($users) . " data karyawan dalam backup\n\n";

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

// Truncate existing users table (optional - uncomment if you want to clear first)
// $pdo->exec("TRUNCATE TABLE users");
// echo "Tabel users dikosongkan\n";

// Get all columns from users table
$stmt = $pdo->query("DESCRIBE users");
$tableColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Memulai update data karyawan...\n";
echo str_repeat("-", 60) . "\n";

$successCount = 0;
$errorCount = 0;

foreach ($users as $user) {
    try {
        // Filter only columns that exist in the table
        $filteredUser = array_filter($user, function($key) use ($tableColumns) {
            return in_array($key, $tableColumns);
        }, ARRAY_FILTER_USE_KEY);
        
        // Build INSERT ... ON DUPLICATE KEY UPDATE query
        $columns = array_keys($filteredUser);
        $placeholders = array_map(fn($col) => ":$col", $columns);
        $updatePairs = array_map(fn($col) => "$col = VALUES($col)", $columns);
        
        $sql = "INSERT INTO users (" . implode(", ", $columns) . ") 
                VALUES (" . implode(", ", $placeholders) . ")
                ON DUPLICATE KEY UPDATE " . implode(", ", $updatePairs);
        
        $stmt = $pdo->prepare($sql);
        
        foreach ($filteredUser as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        $stmt->execute();
        
        $userName = $user['name'] ?? 'Unknown';
        $userId = $user['id'] ?? '?';
        echo "[OK] User ID $userId: $userName\n";
        $successCount++;
        
    } catch (PDOException $e) {
        $userName = $user['name'] ?? 'Unknown';
        $userId = $user['id'] ?? '?';
        echo "[ERROR] User ID $userId ($userName): " . $e->getMessage() . "\n";
        $errorCount++;
    }
}

echo str_repeat("-", 60) . "\n";
echo "\nSelesai!\n";
echo "Berhasil: $successCount\n";
echo "Gagal: $errorCount\n";
echo "Total: " . count($users) . "\n";
