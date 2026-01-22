<?php
// Koneksi langsung ke MySQL pada port 3308
$host = '127.0.0.1';
$port = '3308';
$db   = 'absensi-laravel.sql';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->query("SELECT id, name, email, username, is_admin, created_at FROM users ORDER BY created_at DESC");
    
    echo "\n";
    echo "========================================================================================================================\n";
    echo "                                   DAFTAR SEMUA USER DI DATABASE                                                        \n";
    echo "========================================================================================================================\n";
    echo sprintf("%-4s | %-30s | %-30s | %-15s | %-8s | %-20s\n", "ID", "NAME", "EMAIL", "USERNAME", "ROLE", "CREATED AT");
    echo "------------------------------------------------------------------------------------------------------------------------\n";
    
    $count = 0;
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%-4s | %-30s | %-30s | %-15s | %-8s | %-20s\n",
            $row['id'],
            substr($row['name'] ?? '-', 0, 30),
            substr($row['email'] ?? '-', 0, 30),
            substr($row['username'] ?? '-', 0, 15),
            $row['is_admin'] ?? '-',
            $row['created_at'] ?? '-'
        );
        $count++;
    }
    
    echo "========================================================================================================================\n";
    echo "Total Users: $count\n";
    echo "\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
