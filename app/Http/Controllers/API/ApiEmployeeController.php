<?php

namespace App\Http\Controllers\API;

use App\Helpers\ApiFormatter;
use App\Http\Controllers\Controller;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;

class ApiEmployeeController extends Controller
{
    /**
     * Get all employees
     */
    public function index(Request $request)
    {
        try {
            $query = User::with(['Jabatan', 'Lokasi']);

            // Filter by jabatan_id
            if ($request->has('jabatan_id')) {
                $query->where('jabatan_id', $request->jabatan_id);
            }

            // Filter by is_admin status
            if ($request->has('is_admin')) {
                $query->where('is_admin', $request->is_admin);
            }

            $employees = $query->get();

            return ApiFormatter::createApi(200, 'Success', $employees);
        } catch (Exception $error) {
            return ApiFormatter::createApi(500, 'Failed: ' . $error->getMessage(), null);
        }
    }

    /**
     * Get single employee by ID
     */
    public function show($id)
    {
        try {
            $employee = User::with(['Jabatan', 'Lokasi'])->find($id);

            if (!$employee) {
                return ApiFormatter::createApi(404, 'Employee not found', null);
            }

            return ApiFormatter::createApi(200, 'Success', $employee);
        } catch (Exception $error) {
            return ApiFormatter::createApi(500, 'Failed: ' . $error->getMessage(), null);
        }
    }

    /**
     * Get employee summary stats
     */
    public function summary()
    {
        try {
            $stats = [
                'total' => User::count(),
                'admin' => User::where('is_admin', 'admin')->count(),
                'user' => User::where('is_admin', 'user')->count(),
                'by_jabatan' => User::selectRaw('jabatan_id, COUNT(*) as count')
                    ->with('Jabatan')
                    ->whereNotNull('jabatan_id')
                    ->groupBy('jabatan_id')
                    ->get()
            ];

            return ApiFormatter::createApi(200, 'Success', $stats);
        } catch (Exception $error) {
            return ApiFormatter::createApi(500, 'Failed: ' . $error->getMessage(), null);
        }
    }
}
