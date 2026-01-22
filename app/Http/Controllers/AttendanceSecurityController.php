<?php

namespace App\Http\Controllers;

use App\Models\DailyAttendanceCode;
use App\Models\settings;
use Illuminate\Http\Request;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class AttendanceSecurityController extends Controller
{
    /**
     * Display QR Code page for admin
     */
    public function qrCodePage()
    {
        $settings = settings::first();
        $code = $this->getCurrentCode($settings);
        
        return view('attendance.qr-display', [
            'title' => 'QR Code Absensi Hari Ini',
            'code' => $code,
            'settings' => $settings,
        ]);
    }

    /**
     * Generate new QR code (manual refresh)
     */
    public function regenerateQrCode()
    {
        $settings = settings::first();
        
        if ($settings->qr_rotation == 'hourly') {
            DailyAttendanceCode::getHourlyCode();
        } else {
            DailyAttendanceCode::generateNewCode();
        }
        
        return redirect('/attendance/qr-display')->with('success', 'QR Code berhasil di-generate ulang');
    }

    /**
     * Verify QR code (called when employee scans)
     */
    public function verifyQrCode(Request $request)
    {
        $code = $request->input('code');
        
        if (DailyAttendanceCode::validateCode($code)) {
            return response()->json([
                'valid' => true,
                'message' => 'QR Code valid'
            ]);
        }
        
        return response()->json([
            'valid' => false,
            'message' => 'QR Code tidak valid atau sudah kadaluarsa'
        ], 400);
    }

    /**
     * Check if current request IP is allowed
     */
    public static function checkIpRestriction()
    {
        $settings = settings::first();
        
        if (!$settings || !$settings->enable_ip_restriction) {
            return ['allowed' => true, 'message' => ''];
        }
        
        $allowedIps = json_decode($settings->allowed_ip_addresses, true) ?? [];
        
        if (empty($allowedIps)) {
            return ['allowed' => true, 'message' => ''];
        }
        
        $clientIp = request()->ip();
        
        // Check if client IP is in allowed list
        foreach ($allowedIps as $ip) {
            if ($clientIp === trim($ip)) {
                return ['allowed' => true, 'message' => ''];
            }
            
            // Support CIDR notation (e.g., 192.168.1.0/24)
            if (strpos($ip, '/') !== false && self::ipInRange($clientIp, $ip)) {
                return ['allowed' => true, 'message' => ''];
            }
        }
        
        return [
            'allowed' => false,
            'message' => $settings->ip_restriction_message ?? 'Anda harus terhubung ke WiFi Kantor untuk absensi',
            'client_ip' => $clientIp
        ];
    }

    /**
     * Check if IP is in CIDR range
     */
    private static function ipInRange($ip, $cidr)
    {
        list($subnet, $bits) = explode('/', $cidr);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;
        return ($ip & $mask) == $subnet;
    }

    /**
     * Get current attendance code based on settings
     */
    private function getCurrentCode($settings)
    {
        if ($settings && $settings->qr_rotation == 'hourly') {
            return DailyAttendanceCode::getHourlyCode();
        }
        
        return DailyAttendanceCode::getTodayCode();
    }

    /**
     * API to get current QR code data (for AJAX refresh)
     */
    public function getQrCodeData()
    {
        $settings = settings::first();
        $code = $this->getCurrentCode($settings);
        
        return response()->json([
            'code' => $code->code,
            'url' => $code->qr_image,
            'expires_at' => $code->expires_at ? $code->expires_at->format('H:i') : '23:59',
            'date' => $code->date->format('d M Y'),
        ]);
    }
}
