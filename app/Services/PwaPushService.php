<?php

namespace App\Services;

use App\Models\PwaPushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PwaPushService
{
    public function sendToUser(User $user, array $data): void
    {
        if (!$this->isConfigured()) {
            return;
        }

        $subscriptions = PwaPushSubscription::where('user_id', $user->id)->get();
        if ($subscriptions->isEmpty()) {
            return;
        }

        $payload = json_encode($this->payload($data));
        if (!$payload) {
            return;
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => config('webpush.vapid.subject'),
                'publicKey' => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ],
        ], [
            'TTL' => (int) config('webpush.ttl', 86400),
            'urgency' => $this->urgency($data),
        ]);

        foreach ($subscriptions as $subscription) {
            try {
                $report = $webPush->sendOneNotification(
                    Subscription::create([
                        'endpoint' => $subscription->endpoint,
                        'publicKey' => $subscription->public_key,
                        'authToken' => $subscription->auth_token,
                        'contentEncoding' => $subscription->content_encoding ?: 'aes128gcm',
                    ]),
                    $payload
                );

                if ($report->isSubscriptionExpired()) {
                    $subscription->delete();
                } elseif (!$report->isSuccess()) {
                    Log::warning('PWA push notification failed', [
                        'user_id' => $user->id,
                        'endpoint_hash' => $subscription->endpoint_hash,
                        'reason' => $report->getReason(),
                    ]);
                } else {
                    $subscription->forceFill(['last_used_at' => now()])->save();
                }
            } catch (\Throwable $e) {
                Log::warning('PWA push notification error', [
                    'user_id' => $user->id,
                    'endpoint_hash' => $subscription->endpoint_hash,
                    'message' => $e->getMessage(),
                ]);
            }
        }
    }

    public function payload(array $data): array
    {
        $isStockAlert = (bool) ($data['stock_alert'] ?? false);
        $isEmptyStock = ($data['stock_alert_status'] ?? null) === 'empty';

        return [
            'title' => $isStockAlert ? 'Peringatan Stok' : 'Notifikasi Baru',
            'body' => $data['message'] ?? 'Ada notifikasi baru.',
            'from' => $data['from'] ?? 'Sistem',
            'action' => url($data['action'] ?? '/notifications'),
            'icon' => $this->iconUrl(),
            'badge' => url('/myhr/app/icons/icon-192x192.png'),
            'tag' => $data['stock_alert_key'] ?? ('notification-' . md5(($data['message'] ?? '') . ($data['action'] ?? ''))),
            'renotify' => true,
            'requireInteraction' => $isEmptyStock,
            'vibrate' => $isEmptyStock ? [250, 120, 250, 120, 350] : [180, 90, 180],
            'silent' => false,
            'data' => [
                'url' => url($data['action'] ?? '/notifications'),
                'is_stock_alert' => $isStockAlert,
                'severity' => $data['stock_alert_status'] ?? null,
            ],
        ];
    }

    private function urgency(array $data): string
    {
        if (($data['stock_alert_status'] ?? null) === 'empty') {
            return 'high';
        }

        return 'normal';
    }

    private function iconUrl(): string
    {
        $settings = \App\Models\settings::first();
        if ($settings && $settings->logo) {
            return url('/storage/' . $settings->logo);
        }

        return url('/myhr/app/icons/icon-192x192.png');
    }

    private function isConfigured(): bool
    {
        return Schema::hasTable('pwa_push_subscriptions')
            && config('webpush.vapid.public_key')
            && config('webpush.vapid.private_key');
    }
}
