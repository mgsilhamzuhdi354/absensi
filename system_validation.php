<?php
/**
 * COMPREHENSIVE SYSTEM VALIDATION
 * Tests all critical features to ensure system stability
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$errors = [];
$warnings = [];
$passed = 0;

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║        COMPREHENSIVE SYSTEM VALIDATION                       ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// ===== TEST 1: DATABASE CONNECTION =====
echo "▶ 1. DATABASE CONNECTION\n";
try {
    \DB::connection()->getPdo();
    echo "   ✅ Database connected successfully\n";
    $passed++;
} catch (\Exception $e) {
    $errors[] = "Database connection failed: " . $e->getMessage();
    echo "   ❌ Database connection failed\n";
}

// ===== TEST 2: CRITICAL TABLES =====
echo "\n▶ 2. CRITICAL TABLES\n";
$tables = ['users', 'mapping_shifts', 'laporan_kinerjas', 'jenis_kinerjas', 'shifts', 'lokasis'];
foreach ($tables as $table) {
    try {
        $count = \DB::table($table)->count();
        echo "   ✅ $table: $count records\n";
        $passed++;
    } catch (\Exception $e) {
        $errors[] = "Table $table error: " . $e->getMessage();
        echo "   ❌ $table: ERROR\n";
    }
}

// ===== TEST 3: FACE RECOGNITION DATA =====
echo "\n▶ 3. FACE RECOGNITION SYSTEM\n";
try {
    $columns = \Schema::getColumnListing('users');
    if (in_array('face_descriptor', $columns)) {
        echo "   ✅ face_descriptor column exists\n";
        $passed++;

        $faceUsers = \DB::table('users')
            ->whereNotNull('face_descriptor')
            ->where(\DB::raw('LENGTH(face_descriptor)'), '>', 100)
            ->count();
        echo "   ✅ Users with face data: $faceUsers\n";
        $passed++;
    } else {
        $errors[] = "face_descriptor column missing";
        echo "   ❌ face_descriptor column missing\n";
    }
} catch (\Exception $e) {
    $errors[] = "Face recognition check failed: " . $e->getMessage();
    echo "   ❌ Face recognition check failed\n";
}

// ===== TEST 4: CONTROLLER VALIDATION =====
echo "\n▶ 4. CONTROLLER CLASSES\n";
$controllers = [
    'App\Http\Controllers\authController',
    'App\Http\Controllers\AbsenController',
    'App\Http\Controllers\karyawanController',
    'App\Http\Controllers\LaporanKinerjaController',
];
foreach ($controllers as $ctrl) {
    if (class_exists($ctrl)) {
        echo "   ✅ $ctrl OK\n";
        $passed++;
    } else {
        $errors[] = "Controller missing: $ctrl";
        echo "   ❌ $ctrl MISSING\n";
    }
}

// ===== TEST 5: authController METHODS =====
echo "\n▶ 5. AUTHENTICATION CONTROLLER METHODS\n";
$authMethods = ['attendanceFace', 'login', 'logout'];
foreach ($authMethods as $method) {
    if (method_exists(\App\Http\Controllers\authController::class, $method)) {
        echo "   ✅ authController::$method() exists\n";
        $passed++;
    } else {
        $warnings[] = "Method missing: authController::$method";
        echo "   ⚠️ authController::$method() missing\n";
    }
}

// ===== TEST 6: VIEW FILES =====
echo "\n▶ 6. CRITICAL VIEW FILES\n";
$views = [
    'auth/attendance-face' => 'resources/views/auth/attendance-face.blade.php',
    'karyawan/register-my-face' => 'resources/views/karyawan/register-my-face.blade.php',
    'test-face' => 'resources/views/test-face.blade.php',
    'kinerja-pegawai/indexUser' => 'resources/views/kinerja-pegawai/indexUser.blade.php',
];
foreach ($views as $name => $path) {
    if (file_exists(__DIR__ . '/' . $path)) {
        echo "   ✅ $name exists\n";
        $passed++;
    } else {
        $errors[] = "View missing: $name";
        echo "   ❌ $name MISSING\n";
    }
}

// ===== TEST 7: ML MODELS FILES =====
echo "\n▶ 7. FACE-API ML MODEL FILES\n";
$mlPath = __DIR__ . '/public/face/weights/';
$mlFiles = [
    'tiny_face_detector_model-weights_manifest.json',
    'face_landmark_68_model-weights_manifest.json',
    'face_recognition_model-weights_manifest.json',
];
foreach ($mlFiles as $file) {
    if (file_exists($mlPath . $file)) {
        echo "   ✅ $file exists\n";
        $passed++;
    } else {
        $warnings[] = "ML model missing: $file";
        echo "   ⚠️ $file missing\n";
    }
}

// ===== TEST 8: STORAGE & PERMISSIONS =====
echo "\n▶ 8. STORAGE DIRECTORIES\n";
$storageDirs = ['storage/app', 'storage/logs', 'storage/framework/cache'];
foreach ($storageDirs as $dir) {
    if (is_dir(__DIR__ . '/' . $dir) && is_writable(__DIR__ . '/' . $dir)) {
        echo "   ✅ $dir writable\n";
        $passed++;
    } else {
        $warnings[] = "Storage issue: $dir";
        echo "   ⚠️ $dir not writable\n";
    }
}

// ===== TEST 9: KINERJA DATA VALIDATION =====
echo "\n▶ 9. KINERJA DATA INTEGRITY\n";
try {
    $total = \DB::table('laporan_kinerjas')->count();
    $withJenis = \DB::table('laporan_kinerjas')
        ->whereNotNull('jenis_kinerja_id')
        ->count();
    $zeroValues = \DB::table('laporan_kinerjas')->where('nilai', 0)->count();

    echo "   ✅ Total records: $total\n";
    echo "   ✅ Records with jenis: $withJenis\n";
    echo "   ✅ Zero values (hidden): $zeroValues\n";
    $passed++;
} catch (\Exception $e) {
    $errors[] = "Kinerja validation failed: " . $e->getMessage();
    echo "   ❌ Kinerja validation failed\n";
}

// ===== TEST 10: ROUTE HEALTH CHECK =====
echo "\n▶ 10. CRITICAL ROUTES\n";
$routes = [
    'attendance/face',
    'test-face',
    'login',
    'dashboard',
];
$routeCollection = \Route::getRoutes();
foreach ($routes as $uri) {
    $found = false;
    foreach ($routeCollection as $route) {
        if ($route->uri() === $uri) {
            $found = true;
            break;
        }
    }
    if ($found) {
        echo "   ✅ Route: /$uri\n";
        $passed++;
    } else {
        $warnings[] = "Route not found: $uri";
        echo "   ⚠️ Route: /$uri not found\n";
    }
}

// ===== SUMMARY =====
echo "\n╔══════════════════════════════════════════════════════════════╗\n";
echo "║                    VALIDATION SUMMARY                        ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n";
echo "\n   ✅ PASSED: $passed tests\n";
echo "   ⚠️ WARNINGS: " . count($warnings) . "\n";
echo "   ❌ ERRORS: " . count($errors) . "\n";

if (count($errors) > 0) {
    echo "\n   ERRORS FOUND:\n";
    foreach ($errors as $e) {
        echo "   - $e\n";
    }
}

if (count($warnings) > 0) {
    echo "\n   WARNINGS:\n";
    foreach ($warnings as $w) {
        echo "   - $w\n";
    }
}

if (count($errors) === 0) {
    echo "\n   🎉 SYSTEM STATUS: HEALTHY - NO CRITICAL ISSUES\n";
} else {
    echo "\n   ⚠️ SYSTEM STATUS: ISSUES FOUND - REVIEW REQUIRED\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
