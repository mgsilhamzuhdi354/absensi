<?php

namespace App\Http\Controllers;

use App\Models\PwaPushSubscription;
use App\Services\PwaPushService;
use Illuminate\Http\Request;

class PwaPushController extends Controller
{
    public function publicKey()
    {
        return response()->json([
            'publicKey' => config('webpush.vapid.public_key'),
            'enabled' => (bool) (config('webpush.vapid.public_key') && config('webpush.vapid.private_key')),
        ]);
    }

    public function subscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
            'contentEncoding' => 'nullable|string|max:30',
        ]);

        $endpoint = $validated['endpoint'];

        $subscription = PwaPushSubscription::updateOrCreate(
            ['endpoint_hash' => hash('sha256', $endpoint)],
            [
                'user_id' => auth()->id(),
                'endpoint' => $endpoint,
                'public_key' => $validated['keys']['p256dh'],
                'auth_token' => $validated['keys']['auth'],
                'content_encoding' => $validated['contentEncoding'] ?? 'aes128gcm',
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
                'last_used_at' => now(),
            ]
        );

        if ($subscription->wasRecentlyCreated) {
            $this->sendActivationPush();
            $this->resendUnreadStockAlerts();
        }

        return response()->json([
            'success' => true,
        ]);
    }

    public function unsubscribe(Request $request)
    {
        $validated = $request->validate([
            'endpoint' => 'required|string',
        ]);

        PwaPushSubscription::where('endpoint_hash', hash('sha256', $validated['endpoint']))
            ->where('user_id', auth()->id())
            ->delete();

        return response()->json([
            'success' => true,
        ]);
    }

    private function sendActivationPush(): void
    {
        app(PwaPushService::class)->sendToUser(auth()->user(), [
            'from' => 'Sistem',
            'message' => 'Notifikasi aplikasi aktif. Peringatan stok akan muncul di perangkat ini.',
            'action' => '/notifications',
        ]);
    }

    private function resendUnreadStockAlerts(): void
    {
        auth()->user()
            ->notifications()
            ->whereNull('read_at')
            ->get()
            ->filter(function ($notification) {
                return (bool) ($notification->data['stock_alert'] ?? false);
            })
            ->take(3)
            ->each(function ($notification) {
                app(PwaPushService::class)->sendToUser(auth()->user(), $notification->data);
            });
    }
}
