<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiFormatter;
use App\Http\Controllers\Controller;
use App\Models\MappingShift;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;

class ApiAttendanceController extends Controller
{
    /**
     * Get attendance records with filters
     */
    public function index(Request $request)
    {
        try {
            $query = MappingShift::with(['User', 'Shift']);

            // Filter by date range
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('tanggal', [$request->start_date, $request->end_date]);
            }

            // Filter by employee
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by month/year
            if ($request->has('month') && $request->has('year')) {
                $query->whereYear('tanggal', $request->year)
                    ->whereMonth('tanggal', $request->month);
            }

            $attendance = $query->orderBy('tanggal', 'desc')->limit(100)->get();

            return ApiFormatter::createApi(200, 'Success', $attendance);
        } catch (Exception $error) {
            return ApiFormatter::createApi(500, 'Failed: ' . $error->getMessage(), null);
        }
    }

    /**
     * Get attendance summary for a specific month
     */
    public function summary(Request $request)
    {
        try {
            $month = $request->input('month', date('m'));
            $year = $request->input('year', date('Y'));

            $summary = User::select('users.id', 'users.name', 'users.jabatan_id')
                ->with('Jabatan')
                ->leftJoin('mapping_shifts', function ($join) use ($month, $year) {
                    $join->on('users.id', '=', 'mapping_shifts.user_id')
                        ->whereYear('mapping_shifts.tanggal', $year)
                        ->whereMonth('mapping_shifts.tanggal', $month);
                })
                ->selectRaw('
                    COUNT(mapping_shifts.id) as total_shift,
                    SUM(CASE WHEN mapping_shifts.status_absen = "Masuk" THEN 1 ELSE 0 END) as hadir,
                    SUM(CASE WHEN mapping_shifts.status_absen = "Izin" THEN 1 ELSE 0 END) as izin,
                    SUM(CASE WHEN mapping_shifts.status_absen = "Sakit" THEN 1 ELSE 0 END) as sakit,
                    SUM(CASE WHEN mapping_shifts.status_absen = "Alpha" OR mapping_shifts.status_absen = "Tidak Masuk" THEN 1 ELSE 0 END) as alpha,
                    SUM(CASE WHEN mapping_shifts.telat > 0 THEN 1 ELSE 0 END) as telat
                ')
                ->groupBy('users.id', 'users.name', 'users.jabatan_id')
                ->get();

            return ApiFormatter::createApi(200, 'Success', [
                'month' => $month,
                'year' => $year,
                'data' => $summary
            ]);
        } catch (Exception $error) {
            return ApiFormatter::createApi(500, 'Failed: ' . $error->getMessage(), null);
        }
    }

    /**
     * Get attendance for specific employee
     */
    public function byEmployee($employeeId, Request $request)
    {
        try {
            $month = $request->input('month', date('m'));
            $year = $request->input('year', date('Y'));

            $employee = User::find($employeeId);
            if (!$employee) {
                return ApiFormatter::createApi(404, 'Employee not found', null);
            }

            $attendance = MappingShift::with('Shift')
                ->where('user_id', $employeeId)
                ->whereYear('tanggal', $year)
                ->whereMonth('tanggal', $month)
                ->orderBy('tanggal', 'asc')
                ->get();

            $stats = [
                'hadir' => $attendance->where('status_absen', 'Masuk')->count(),
                'izin' => $attendance->where('status_absen', 'Izin')->count(),
                'sakit' => $attendance->where('status_absen', 'Sakit')->count(),
                'alpha' => $attendance->whereIn('status_absen', ['Alpha', 'Tidak Masuk'])->count(),
                'telat' => $attendance->where('telat', '>', 0)->count(),
            ];

            return ApiFormatter::createApi(200, 'Success', [
                'employee' => $employee,
                'month' => $month,
                'year' => $year,
                'stats' => $stats,
                'records' => $attendance
            ]);
        } catch (Exception $error) {
            return ApiFormatter::createApi(500, 'Failed: ' . $error->getMessage(), null);
        }
    }
}
