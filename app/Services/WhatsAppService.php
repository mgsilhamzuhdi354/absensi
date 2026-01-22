<?php

namespace App\Services;

use App\Models\settings;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    /**
     * Send WhatsApp message using Fonnte API
     * 
     * @param string $phoneNumber Target phone number (format: 628xxx)
     * @param string $message Message to send
     * @return bool
     */
    public static function send($phoneNumber, $message)
    {
        try {
            $settings = settings::first();
            
            if (!$settings || !$settings->api_url || !$settings->api_whatsapp) {
                return false;
            }

            $response = Http::withHeaders([
                'Authorization' => $settings->api_whatsapp
            ])->post($settings->api_url, [
                'target' => $phoneNumber,
                'message' => $message,
            ]);

            if ($response->successful()) {
                Log::info('WhatsApp sent successfully to ' . $phoneNumber);
                return true;
            } else {
                Log::error('WhatsApp failed: ' . $response->body());
                return false;
            }
        } catch (\Exception $e) {
            Log::error('WhatsApp error: ' . $e->getMessage());
            return false;
        }
    }
}
