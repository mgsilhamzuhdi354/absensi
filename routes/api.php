<?php

use App\Http\Controllers\API\UsersController;
use App\Http\Controllers\API\ApiEmployeeController;
use App\Http\Controllers\API\ApiAttendanceController;
use App\Http\Controllers\API\ApiPayrollController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Legacy routes
Route::get('users', [UsersController::class, 'index']);
Route::post('tambah-users', [UsersController::class, 'store']);

// Employee API (for ERP integration)
Route::get('employees', [ApiEmployeeController::class, 'index']);
Route::get('employees/summary', [ApiEmployeeController::class, 'summary']);
Route::get('employees/{id}', [ApiEmployeeController::class, 'show']);

// Attendance API
Route::get('attendance', [ApiAttendanceController::class, 'index']);
Route::get('attendance/summary', [ApiAttendanceController::class, 'summary']);
Route::get('attendance/employee/{id}', [ApiAttendanceController::class, 'byEmployee']);

// Payroll API
Route::get('payroll', [ApiPayrollController::class, 'index']);
Route::get('payroll/summary', [ApiPayrollController::class, 'summary']);
Route::get('payroll/employee/{id}', [ApiPayrollController::class, 'byEmployee']);

// Performance/Kinerja API (for ERP integration)
Route::get('performance', [\App\Http\Controllers\API\ApiPerformanceController::class, 'index']);
Route::get('performance/summary', [\App\Http\Controllers\API\ApiPerformanceController::class, 'summary']);
Route::get('performance/employee/{id}', [\App\Http\Controllers\API\ApiPerformanceController::class, 'byEmployee']);

// Face Recognition API (for public attendance)

// Get ALL users with face descriptors (for auto-detect)
Route::get('face-descriptors-all', function () {
    $users = \App\Models\User::whereNotNull('face_descriptor')
        ->where('face_descriptor', '!=', '')
        ->select('id', 'name', 'username', 'nip', 'face_descriptor')
        ->get();

    return response()->json([
        'success' => true,
        'count' => $users->count(),
        'users' => $users
    ]);
});

// Get single user descriptor
Route::get('face-descriptor/{username}', function ($username) {
    $user = \App\Models\User::where('username', $username)
        ->orWhere('nip', $username)
        ->first();

    if (!$user) {
        return response()->json([
            'success' => false,
            'message' => 'User tidak ditemukan'
        ]);
    }

    if (empty($user->face_descriptor)) {
        return response()->json([
            'success' => false,
            'message' => 'User belum registrasi wajah. Silakan login dan daftarkan wajah di My Profile.'
        ]);
    }

    return response()->json([
        'success' => true,
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username
        ],
        'descriptor' => $user->face_descriptor
    ]);
});

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
