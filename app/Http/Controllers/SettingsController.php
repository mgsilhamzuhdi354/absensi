<?php

namespace App\Http\Controllers;

use App\Models\settings;
use App\Services\EmployeeQrService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index()
    {
        $title = 'Settings';
        $data = settings::first();
        return view('settings.index', compact(
            'title',
            'data'
        ));
    }

    public function store(Request $request)
    {
        $settings = settings::first();

        $validated = $request->validate([
            'name' => 'required',
            'logo' => 'image|file|max:10240|nullable',
            'alamat' => 'nullable',
            'phone' => 'nullable',
            'whatsapp' => 'nullable',
            'api_url' => 'nullable',
            'api_whatsapp' => 'nullable',
            'email' => 'nullable',
            'theme_color' => 'nullable|string',
            'theme_mode' => 'nullable|in:light,dark',
            'bpjs_jht_karyawan_persen' => 'nullable|numeric|min:0|max:100',
            'bpjs_jkk_persen' => 'nullable|numeric|min:0|max:100',
            'bpjs_jkm_persen' => 'nullable|numeric|min:0|max:100',
            'bpjs_jht_perusahaan_persen' => 'nullable|numeric|min:0|max:100',
            // Attendance Security
            'ip_restriction_message' => 'nullable|string',
            'qr_rotation' => 'nullable|in:daily,hourly',
            // Attendance Time Buffer
            'absen_masuk_buffer_menit' => 'nullable|integer|min:0|max:120',
            'absen_pulang_buffer_menit' => 'nullable|integer|min:0|max:120',
            // Face Verification Fallback
            'face_match_threshold' => 'nullable|numeric|min:50|max:100',
            'face_verification_message' => 'nullable|string|max:500',
            'employee_qr_visible_fields' => 'nullable|array',
            'employee_qr_visible_fields.*' => ['string', Rule::in(array_keys(EmployeeQrService::allowedFields()))],
        ]);

        if ($request->file('logo')) {
            $validated['logo'] = $request->file('logo')->store('logo', 'public');
        }

        // Handle boolean fields
        $validated['enable_ip_restriction'] = $request->has('enable_ip_restriction');
        $validated['enable_daily_qr'] = $request->has('enable_daily_qr');
        $validated['enable_face_verification_fallback'] = $request->has('enable_face_verification_fallback');

        // Handle IP addresses array
        if ($request->has('ip_addresses')) {
            $ipAddresses = array_filter($request->input('ip_addresses'), function ($ip) {
                return !empty(trim($ip));
            });
            $validated['allowed_ip_addresses'] = json_encode(array_values($ipAddresses));
        }

        $selectedQrFields = $validated['employee_qr_visible_fields'] ?? EmployeeQrService::defaultVisibleFields();
        unset($validated['employee_qr_visible_fields']);
        $selectedQrFields = array_values(array_intersect(
            $selectedQrFields,
            array_keys(EmployeeQrService::allowedFields())
        ));

        if (empty($selectedQrFields)) {
            $selectedQrFields = EmployeeQrService::defaultVisibleFields();
        }

        $validated['employee_qr_visible_fields'] = json_encode($selectedQrFields);

        $settings->update($validated);
        return back()->with('success', 'Data Berhasil Ditambahkan');
    }
}

