<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\PwaPushSubscription;
use App\Models\settings;
use App\Models\User;
use App\Services\PwaPushService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PwaPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        settings::create([
            'name' => 'Absensi',
            'logo' => 'logo/absensi.png',
        ]);

        $jabatan = Jabatan::create([
            'nama_jabatan' => 'Crew',
        ]);

        $this->user = User::create([
            'name' => 'PWA User',
            'username' => 'pwa-user',
            'email' => 'pwa-user@example.test',
            'is_admin' => 'admin',
            'jabatan_id' => $jabatan->id,
        ]);
    }

    /** @test */
    public function authenticated_user_can_store_and_remove_pwa_push_subscription()
    {
        $payload = [
            'endpoint' => 'https://push.example.test/subscription/abc',
            'keys' => [
                'p256dh' => 'public-key',
                'auth' => 'auth-token',
            ],
            'contentEncoding' => 'aes128gcm',
        ];

        $this->actingAs($this->user)
            ->postJson('/pwa-push/subscribe', $payload)
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('pwa_push_subscriptions', [
            'user_id' => $this->user->id,
            'endpoint_hash' => hash('sha256', $payload['endpoint']),
            'content_encoding' => 'aes128gcm',
        ]);

        $this->actingAs($this->user)
            ->postJson('/pwa-push/subscribe', $payload)
            ->assertOk();

        $this->assertSame(1, PwaPushSubscription::count());

        $this->actingAs($this->user)
            ->deleteJson('/pwa-push/unsubscribe', [
                'endpoint' => $payload['endpoint'],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(0, PwaPushSubscription::count());
    }

    /** @test */
    public function pwa_push_public_key_endpoint_reports_configuration_state()
    {
        $this->actingAs($this->user)
            ->getJson('/pwa-push/public-key')
            ->assertOk()
            ->assertJson([
                'enabled' => false,
            ]);

        config([
            'webpush.vapid.public_key' => 'public-key',
            'webpush.vapid.private_key' => 'private-key',
        ]);

        $this->actingAs($this->user)
            ->getJson('/pwa-push/public-key')
            ->assertOk()
            ->assertJson([
                'enabled' => true,
                'publicKey' => 'public-key',
            ]);
    }

    /** @test */
    public function pwa_push_payload_uses_phone_notification_sound_and_vibration()
    {
        $payload = app(PwaPushService::class)->payload([
            'from' => 'Sistem Stok',
            'message' => 'Stok Pulpen habis.',
            'action' => '/atk/1/detail',
            'stock_alert' => true,
            'stock_alert_status' => 'empty',
            'stock_alert_key' => 'atk:1',
        ]);

        $this->assertSame('Peringatan Stok', $payload['title']);
        $this->assertSame('Stok Pulpen habis.', $payload['body']);
        $this->assertFalse($payload['silent']);
        $this->assertTrue($payload['requireInteraction']);
        $this->assertNotEmpty($payload['vibrate']);
        $this->assertSame(url('/atk/1/detail'), $payload['data']['url']);
    }
}
