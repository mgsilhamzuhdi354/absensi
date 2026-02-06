<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiFormatter;
use App\Http\Controllers\Controller;
use App\Models\LaporanKinerja;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;

class ApiPerformanceController extends Controller
{
    /**
     * Get all performance data with filters
     */
    public function index(Request $request)
    {
        try {
            $query = LaporanKinerja::with(['user', 'jenis']);

            // Filter by month/year
            if ($request->has('month') && $request->has('year')) {
                $query->whereYear('tanggal', $request->year)
                    ->whereMonth('tanggal', $request->month);
            }

            // Filter by employee
            if ($request->has('employee_id')) {
                $query->where('user_id', $request->employee_id);
            }

            $performance = $query->orderBy('tanggal', 'desc')->limit(200)->get();

            return ApiFormatter::createApi(200, 'Success', $performance);
        } catch (Exception $error) {
            return ApiFormatter::createApi(500, 'Failed: ' . $error->getMessage(), null);
        }
    }

    /**
     * Get performance summary for all employees
     * Returns both monthly totals and all-time running scores
     */
    public function summary(Request $request)
    {
        try {
            $month = $request->input('month', date('m'));
            $year = $request->input('year', date('Y'));

            // Get all users with their all-time running score AND monthly score
            $summary = User::select('users.id', 'users.name', 'users.jabatan_id')
                ->with('Jabatan')
                ->leftJoin('laporan_kinerjas', 'users.id', '=', 'laporan_kinerjas.user_id')
                ->selectRaw('
                    MAX(laporan_kinerjas.penilaian_berjalan) as running_score,
                    COUNT(laporan_kinerjas.id) as total_entries,
                    SUM(CASE WHEN YEAR(laporan_kinerjas.tanggal) = ? AND MONTH(laporan_kinerjas.tanggal) = ? THEN laporan_kinerjas.nilai ELSE 0 END) as monthly_score,
                    COUNT(CASE WHEN YEAR(laporan_kinerjas.tanggal) = ? AND MONTH(laporan_kinerjas.tanggal) = ? THEN laporan_kinerjas.id END) as monthly_entries
                ', [$year, $month, $year, $month])
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
     * Get performance for specific employee
     */
    public function byEmployee($employeeId, Request $request)
    {
        try {
            $employee = User::find($employeeId);
            if (!$employee) {
                return ApiFormatter::createApi(404, 'Employee not found', null);
            }

            $month = $request->input('month');
            $year = $request->input('year');

            $query = LaporanKinerja::with('jenis')
                ->where('user_id', $employeeId);

            if ($month && $year) {
                $query->whereYear('tanggal', $year)
                    ->whereMonth('tanggal', $month);
            }

            $performance = $query->orderBy('tanggal', 'desc')->get();

            // Get latest running total
            $latestScore = LaporanKinerja::where('user_id', $employeeId)
                ->latest()
                ->first();

            // Group by jenis_kinerja for breakdown
            $breakdown = $performance->groupBy('jenis_kinerja_id')->map(function ($group) {
                return [
                    'jenis' => $group->first()->jenis->nama ?? 'Unknown',
                    'total' => $group->sum('nilai'),
                    'count' => $group->count()
                ];
            })->values();

            return ApiFormatter::createApi(200, 'Success', [
                'employee' => [
                    'id' => $employee->id,
                    'name' => $employee->name,
                    'position' => $employee->Jabatan->jabatan ?? '-'
                ],
                'current_score' => $latestScore->penilaian_berjalan ?? 0,
                'total_score' => $performance->sum('nilai'),
                'breakdown' => $breakdown,
                'records' => $performance
            ]);
        } catch (Exception $error) {
            return ApiFormatter::createApi(500, 'Failed: ' . $error->getMessage(), null);
        }
    }
}
