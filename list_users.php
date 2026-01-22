<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$users = \App\Models\User::orderBy('created_at', 'DESC')->get();

echo "\n=== DAFTAR SEMUA USER DI DATABASE ===\n";
echo str_repeat("=", 120) . "\n";
printf("%-4s | %-30s | %-30s | %-15s | %-8s | %-20s\n", "ID", "NAME", "EMAIL", "USERNAME", "ROLE", "CREATED AT");
echo str_repeat("-", 120) . "\n";

foreach($users as $u) {
    printf("%-4s | %-30s | %-30s | %-15s | %-8s | %-20s\n", 
        $u->id, 
        substr($u->name, 0, 30),
        substr($u->email, 0, 30),
        substr($u->username ?? '-', 0, 15),
        $u->is_admin,
        $u->created_at
    );
}

echo str_repeat("=", 120) . "\n";
echo "Total Users: " . $users->count() . "\n";
