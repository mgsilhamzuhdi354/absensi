<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\settings;
use App\Services\EmployeeQrService;

class EmployeeQrController extends Controller
{
    private $qrService;

    public function __construct(EmployeeQrService $qrService)
    {
        $this->qrService = $qrService;
    }

    public function show($token)
    {
        $user = User::with(['Jabatan', 'Lokasi'])
            ->where('employee_qr_token', $token)
            ->firstOrFail();
        $setting = settings::first();

        return view('karyawan.public-id-card', [
            'user' => $user,
            'settings' => $setting,
            'visibleFields' => $this->qrService->visibleFields($setting),
            'profileRows' => $this->qrService->publicProfileRows($user, $setting),
            'photoUrl' => $this->qrService->photoUrl($user),
            'logoUrl' => $this->qrService->logoUrl($setting),
        ]);
    }

    public function vcard($token)
    {
        $user = User::with(['Jabatan', 'Lokasi'])
            ->where('employee_qr_token', $token)
            ->firstOrFail();
        $setting = settings::first();
        $filename = $this->qrService->safeFilename($user->name ?: $user->username ?: $user->id) . '.vcf';

        return response($this->qrService->vcardFor($user, $setting), 200, [
            'Content-Type' => 'text/vcard; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
