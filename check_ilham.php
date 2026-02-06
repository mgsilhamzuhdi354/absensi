<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=absensi_laravel', 'root', '');
$stmt = $pdo->query("SELECT lk.id, u.name, lk.tanggal, jk.nama as jenis, lk.nilai, lk.penilaian_berjalan 
                     FROM laporan_kinerjas lk 
                     LEFT JOIN users u ON lk.user_id = u.id 
                     LEFT JOIN jenis_kinerjas jk ON lk.jenis_kinerja_id = jk.id 
                     WHERE u.name LIKE '%Ilham%' 
                     ORDER BY lk.id DESC");
echo "=== SKOR KINERJA ILHAM ===\n\n";
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
    echo "ID: {$r['id']} | {$r['tanggal']} | {$r['jenis']} | Nilai: {$r['nilai']} | Score Total: {$r['penilaian_berjalan']}\n";
}
