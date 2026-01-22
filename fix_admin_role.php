<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

echo "Starting Admin Role Restoration...\n";

// 1. Ensure 'admin' role exists
if (!Role::where('name', 'admin')->exists()) {
    echo "Role 'admin' does not exist. Creating it...\n";
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    echo "Role 'admin' created.\n";
} else {
    echo "Role 'admin' already exists.\n";
}

// 2. Find the admin user
$username = 'admin'; // Based on your description
$user = User::where('username', $username)->first();

if (!$user) {
    echo "User with username '{$username}' not found!\n";
    exit(1);
}

echo "Found user: {$user->name} ({$user->email})\n";

// 3. Assign role
if (!$user->hasRole('admin')) {
    echo "User does not have 'admin' role. Assigning...\n";
    $user->assignRole('admin');
    echo "Role 'admin' assigned to user.\n";
} else {
    echo "User already has 'admin' role.\n";
}

// 4. Force update is_admin column just in case
if ($user->is_admin !== 'admin') {
    echo "Updating is_admin column from '{$user->is_admin}' to 'admin'...\n";
    $user->update(['is_admin' => 'admin']);
    echo "is_admin column updated.\n";
} else {
    echo "is_admin column is already 'admin'.\n";
}

echo "Restoration Complete. Please try logging in.\n";
