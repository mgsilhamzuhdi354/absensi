<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiFormatter;
use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;

class ApiPayrollController extends Controller
{
    /**
     * Get all payroll records
     */
    public function index(Request $request)
    {
        try {
            $query = Payroll::with(['user', 'user.Jabatan', 'user.Lokasi']);

            // Filter by month/year
            if ($request->has('bulan') && $request->has('tahun')) {
                $query->where('bulan', $request->bulan)
                    ->where('tahun', $request->tahun);
            }

            // Filter by employee
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            $payrolls = $query->orderBy('tahun', 'desc')
                ->orderBy('bulan', 'desc')
                ->get();

            return ApiFormatter::createApi(200, 'Success', $payrolls);
        } catch (Exception $error) {
            return ApiFormatter::createApi(500, 'Failed: ' . $error->getMessage(), null);
        }
    }

    /**
     * Get payroll summary for a specific period
     */
    public function summary(Request $request)
    {
        try {
            $bulan = $request->input('bulan', date('m'));
            $tahun = $request->input('tahun', date('Y'));

            $payrolls = Payroll::with(['user', 'user.Jabatan', 'user.Lokasi'])
                ->where('bulan', $bulan)
                ->where('tahun', $tahun)
                ->get();

            $summary = [
                'bulan' => $bulan,
                'tahun' => $tahun,
                'total_karyawan' => $payrolls->count(),
                'total_gaji' => $payrolls->sum('gaji_pokok'),
                'total_tunjangan' => $payrolls->sum('uang_transport'),
                'total_potongan' => $payrolls->sum('total_mangkir') + $payrolls->sum('total_terlambat'),
                'total_lembur' => $payrolls->sum('total_lembur'),
                'grand_total' => $payrolls->sum('grand_total'),
                'data' => $payrolls
            ];

            return ApiFormatter::createApi(200, 'Success', $summary);
        } catch (Exception $error) {
            return ApiFormatter::createApi(500, 'Failed: ' . $error->getMessage(), null);
        }
    }

    /**
     * Get payroll for specific employee
     */
    public function byEmployee($employeeId, Request $request)
    {
        try {
            $employee = User::find($employeeId);
            if (!$employee) {
                return ApiFormatter::createApi(404, 'Employee not found', null);
            }

            $query = Payroll::where('user_id', $employeeId);

            // Optional month/year filter
            if ($request->has('bulan') && $request->has('tahun')) {
                $query->where('bulan', $request->bulan)
                    ->where('tahun', $request->tahun);
            }

            $payrolls = $query->orderBy('tahun', 'desc')
                ->orderBy('bulan', 'desc')
                ->get();

            return ApiFormatter::createApi(200, 'Success', [
                'employee' => $employee,
                'payrolls' => $payrolls
            ]);
        } catch (Exception $error) {
            return ApiFormatter::createApi(500, 'Failed: ' . $error->getMessage(), null);
        }
    }
}
