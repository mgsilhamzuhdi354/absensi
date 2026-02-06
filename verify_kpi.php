<?php
/**
 * Verify KPI data in database
 */

$host = '127.0.0.1';
$dbname = 'absensi_laravel';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== VERIFIKASI DATA KPI DI DATABASE ===\n\n";

    // Check laporan_kinerjas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM laporan_kinerjas");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "laporan_kinerjas: " . $result['total'] . " records\n";

    // Check jenis_kinerjas
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM jenis_kinerjas");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "jenis_kinerjas: " . $result['total'] . " records\n\n";

    // Sample laporan_kinerjas data
    echo "=== SAMPLE DATA laporan_kinerjas (5 terakhir) ===\n";
    $stmt = $pdo->query("SELECT lk.id, u.name, lk.tanggal, jk.nama as jenis, lk.nilai, lk.penilaian_berjalan 
                         FROM laporan_kinerjas lk 
                         LEFT JOIN users u ON lk.user_id = u.id
                         LEFT JOIN jenis_kinerjas jk ON lk.jenis_kinerja_id = jk.id
                         ORDER BY lk.id DESC LIMIT 5");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        echo "ID: {$row['id']} | {$row['name']} | {$row['tanggal']} | {$row['jenis']} | Nilai: {$row['nilai']} | Score: {$row['penilaian_berjalan']}\n";
    }

    echo "\n=== JENIS KINERJA ===\n";
    $stmt = $pdo->query("SELECT id, nama, bobot FROM jenis_kinerjas");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        echo "ID: {$row['id']} | {$row['nama']} (bobot: {$row['bobot']})\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
