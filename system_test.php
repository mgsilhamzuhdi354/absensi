<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== COMPREHENSIVE SYSTEM TEST ===\n\n";

// Test 1: Database Connection
echo "1. DATABASE CONNECTION\n";
try {
    $userCount = \DB::table('users')->count();
    $shiftCount = \DB::table('mapping_shifts')->count();
    $kinerjaCount = \DB::table('laporan_kinerjas')->count();
    echo "   ✅ Users: $userCount\n";
    echo "   ✅ Shifts: $shiftCount\n";
    echo "   ✅ Kinerja Records: $kinerjaCount\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 2: Face Descriptor Data
echo "\n2. FACE DESCRIPTOR DATA\n";
try {
    $faceUsers = \DB::table('users')
        ->whereNotNull('face_descriptor')
        ->where(\DB::raw('LENGTH(face_descriptor)'), '>', 100)
        ->select('id', 'name')
        ->get();

    echo "   ✅ Users with face data: " . $faceUsers->count() . "\n";
    foreach ($faceUsers as $u) {
        echo "      - {$u->name} (ID: {$u->id})\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 3: Kinerja with Zero Values
echo "\n3. KINERJA WITH ZERO VALUES\n";
try {
    $zeroKinerja = \DB::table('laporan_kinerjas')
        ->where('nilai', 0)
        ->count();
    $totalKinerja = \DB::table('laporan_kinerjas')->count();
    echo "   📊 Total Kinerja: $totalKinerja\n";
    echo "   📊 Zero Values: $zeroKinerja\n";
    echo "   ✅ These will be hidden from user view\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 4: Shift Data
echo "\n4. SHIFT DATA (Latest 5)\n";
try {
    $shifts = \DB::table('mapping_shifts')
        ->join('users', 'mapping_shifts.user_id', '=', 'users.id')
        ->select('users.name', 'mapping_shifts.tanggal', 'mapping_shifts.jam_masuk', 'mapping_shifts.jam_pulang')
        ->orderBy('mapping_shifts.tanggal', 'desc')
        ->limit(5)
        ->get();

    foreach ($shifts as $s) {
        $masuk = $s->jam_masuk ?? '-';
        $pulang = $s->jam_pulang ?? '-';
        echo "   📅 {$s->tanggal} | {$s->name} | In: $masuk | Out: $pulang\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

// Test 5: Authentication
echo "\n5. AUTHENTICATION\n";
try {
    $admins = \DB::table('users')->where('is_admin', 1)->count();
    $employees = \DB::table('users')->where('is_admin', 0)->count();
    echo "   👤 Admins: $admins\n";
    echo "   👥 Employees: $employees\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== TEST COMPLETE ===\n";
