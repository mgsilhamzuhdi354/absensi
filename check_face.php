<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$users = \DB::table('users')
    ->select('id', 'name', 'username', 'face_descriptor')
    ->get();

echo "Total users: " . $users->count() . "\n";
echo "---\n";

foreach ($users as $user) {
    $hasDescriptor = !empty($user->face_descriptor) && $user->face_descriptor !== '';
    $len = strlen($user->face_descriptor ?? '');
    echo "ID: {$user->id}, Name: {$user->name}, Has descriptor: " . ($hasDescriptor ? "YES ($len chars)" : "NO") . "\n";
}
