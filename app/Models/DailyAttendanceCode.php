<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class DailyAttendanceCode extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'date' => 'date',
        'expires_at' => 'datetime',
    ];

    /**
     * Generate or get today's attendance code
     */
    public static function getTodayCode()
    {
        $today = now()->toDateString();
        
        $code = self::where('date', $today)->first();
        
        if (!$code) {
            $code = self::generateNewCode($today);
        }
        
        return $code;
    }

    /**
     * Generate a new attendance code for a specific date
     */
    public static function generateNewCode($date = null)
    {
        $date = $date ?? now()->toDateString();
        
        // Delete old code for this date if exists
        self::where('date', $date)->delete();
        
        // Generate unique code
        $uniqueCode = hash('sha256', config('app.key') . $date . Str::random(32));
        
        // Generate QR code as SVG (simple implementation without external package)
        $qrContent = url('/attendance/verify/' . $uniqueCode);
        
        $code = self::create([
            'code' => $uniqueCode,
            'date' => $date,
            'qr_image' => $qrContent, // Store the URL, will generate QR on display
            'expires_at' => now()->endOfDay(),
        ]);
        
        return $code;
    }

    /**
     * Validate an attendance code
     */
    public static function validateCode($code)
    {
        $today = now()->toDateString();
        
        $validCode = self::where('code', $code)
            ->where('date', $today)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
        
        return $validCode !== null;
    }

    /**
     * Get hourly code (rotates every hour)
     */
    public static function getHourlyCode()
    {
        $currentHour = now()->format('Y-m-d H');
        $codeIdentifier = hash('sha256', $currentHour . config('app.key'));
        
        $code = self::where('code', 'LIKE', substr($codeIdentifier, 0, 16) . '%')
            ->where('date', now()->toDateString())
            ->first();
        
        if (!$code) {
            // Delete old codes for today
            self::where('date', now()->toDateString())->delete();
            
            $code = self::create([
                'code' => $codeIdentifier,
                'date' => now()->toDateString(),
                'qr_image' => url('/attendance/verify/' . $codeIdentifier),
                'expires_at' => now()->addHour(),
            ]);
        }
        
        return $code;
    }
}
