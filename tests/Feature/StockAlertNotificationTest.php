<?php

namespace Tests\Feature;

use App\Models\Atk;
use App\Models\Inventory;
use App\Models\Jabatan;
use App\Models\Lokasi;
use App\Models\settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockAlertNotificationTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $employee;
    private $jabatan;
    private $lokasi;

    protected function setUp(): void
    {
        parent::setUp();

        settings::create([
            'name' => 'Absensi',
            'logo' => 'logo/absensi.png',
        ]);

        $this->jabatan = Jabatan::create([
            'nama_jabatan' => 'Admin Office',
        ]);

        $this->lokasi = Lokasi::create([
            'nama_lokasi' => 'Gudang',
        ]);

        $this->admin = User::create([
            'name' => 'Admin Stok',
            'username' => 'admin-stok',
            'email' => 'admin-stok@example.test',
            'is_admin' => 'admin',
            'jabatan_id' => $this->jabatan->id,
            'lokasi_id' => $this->lokasi->id,
        ]);

        $this->employee = User::create([
            'name' => 'Employee Stok',
            'username' => 'employee-stok',
            'email' => 'employee-stok@example.test',
            'is_admin' => 'user',
            'jabatan_id' => $this->jabatan->id,
            'lokasi_id' => $this->lokasi->id,
        ]);
    }

    /** @test */
    public function atk_low_and_empty_stock_create_admin_notifications_without_duplicates()
    {
        $this->actingAs($this->admin)->post('/atk/store', [
            'nama_atk' => 'Pulpen Merah',
            'kategori' => 'Alat Tulis',
            'stok' => 4,
            'satuan' => 'Pcs',
            'lokasi' => 'Lemari Admin',
            'active' => 1,
        ])->assertRedirect('/atk/1/detail');

        $atk = Atk::firstOrFail();
        $this->assertSame(1, $this->admin->notifications()->count());
        $this->assertDatabaseHas('stock_alerts', [
            'source' => 'atk',
            'alertable_type' => Atk::class,
            'alertable_id' => $atk->id,
            'status' => 'low',
        ]);
        $this->assertStringContainsString('menipis', $this->admin->notifications()->first()->data['message']);

        $this->actingAs($this->admin)->get('/dashboard')->assertOk();
        $this->actingAs($this->admin)->get('/dashboard')->assertOk();
        $this->assertSame(1, $this->admin->notifications()->count());

        $this->actingAs($this->admin)->post('/atk/' . $atk->id . '/stock-out', [
            'tanggal_transaksi' => '2026-06-22',
            'jumlah' => 4,
            'penerima_barang' => 'Operasional',
        ])->assertRedirect('/atk/' . $atk->id . '/detail');

        $this->assertSame(2, $this->admin->notifications()->count());
        $emptyNotification = $this->admin->notifications()
            ->get()
            ->first(fn ($notification) => ($notification->data['stock_alert_status'] ?? null) === 'empty');
        $this->assertNotNull($emptyNotification);
        $this->assertTrue((bool) $emptyNotification->data['stock_alert']);
        $this->assertStringContainsString('habis', $emptyNotification->data['message']);

        $this->actingAs($this->admin)
            ->getJson('/notifications/check-new?since=' . now()->addDay()->timestamp . '&include_existing_stock_alerts=1')
            ->assertOk()
            ->assertJsonFragment([
                'is_stock_alert' => true,
                'severity' => 'empty',
            ]);

        $this->actingAs($this->admin)->post('/atk/' . $atk->id . '/stock-in', [
            'tanggal_transaksi' => '2026-06-22',
            'jumlah' => 10,
            'sumber_barang' => 'Supplier',
        ])->assertRedirect('/atk/' . $atk->id . '/detail');

        $this->assertDatabaseHas('stock_alerts', [
            'source' => 'atk',
            'alertable_id' => $atk->id,
            'status' => 'normal',
        ]);
        $this->assertSame(2, $this->admin->notifications()->count());
    }

    /** @test */
    public function inventory_empty_stock_creates_admin_notification_and_notification_page_renders()
    {
        $inventory = Inventory::create([
            'kode_barang' => 'INV/000999',
            'nama_barang' => 'Laptop Operasional',
            'jenis_barang' => 'Laptop',
            'merk_tipe' => 'Dell',
            'serial_number' => 'SN-STOCK',
            'kondisi' => 'Baik',
            'status_barang' => 'Aktif',
            'tanggal_masuk' => '2026-06-22',
            'stok' => 1,
            'uom' => 'Unit',
            'lokasi_id' => $this->lokasi->id,
            'jabatan_id' => $this->jabatan->id,
        ]);

        $this->actingAs($this->admin)->post('/inventory/' . $inventory->id . '/stock-out', [
            'tanggal_transaksi' => '2026-06-22',
            'jumlah' => 1,
            'penerima_user_id' => $this->employee->id,
            'buat_bast_otomatis' => 0,
        ])->assertRedirect('/inventory/' . $inventory->id . '/detail');

        $notification = $this->admin->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertSame('Sistem Stok', $notification->data['from']);
        $this->assertSame('empty', $notification->data['stock_alert_status']);
        $this->assertStringContainsString('Laptop Operasional', $notification->data['message']);

        $this->actingAs($this->admin)
            ->get('/notifications')
            ->assertOk()
            ->assertSee('Sistem Stok')
            ->assertSee('Laptop Operasional');
    }

    /** @test */
    public function disabled_atk_stock_alert_does_not_create_notifications_until_enabled()
    {
        $this->actingAs($this->admin)->post('/atk/store', [
            'nama_atk' => 'Kertas Memo',
            'kategori' => 'Alat Tulis',
            'stok' => 3,
            'satuan' => 'Pcs',
            'lokasi' => 'Lemari Admin',
            'active' => 1,
            'stock_alert_enabled' => 0,
        ])->assertRedirect('/atk/1/detail');

        $atk = Atk::firstOrFail();
        $this->assertFalse((bool) $atk->stock_alert_enabled);
        $this->assertSame(0, $this->admin->notifications()->count());
        $this->assertDatabaseMissing('stock_alerts', [
            'alertable_type' => Atk::class,
            'alertable_id' => $atk->id,
        ]);

        $this->actingAs($this->admin)
            ->put('/atk/' . $atk->id . '/stock-alert', [
                'stock_alert_enabled' => 1,
            ])
            ->assertRedirect();

        $atk->refresh();
        $this->assertTrue((bool) $atk->stock_alert_enabled);
        $this->assertSame(1, $this->admin->notifications()->count());
        $this->assertDatabaseHas('stock_alerts', [
            'source' => 'atk',
            'alertable_type' => Atk::class,
            'alertable_id' => $atk->id,
            'status' => 'low',
        ]);
    }

    /** @test */
    public function disabled_inventory_stock_alert_does_not_create_notifications_until_enabled()
    {
        $inventory = Inventory::create([
            'kode_barang' => 'INV/000998',
            'nama_barang' => 'Mouse Cadangan',
            'jenis_barang' => 'Peripheral',
            'merk_tipe' => 'Logitech',
            'serial_number' => 'SN-MOUSE',
            'kondisi' => 'Baik',
            'status_barang' => 'Aktif',
            'tanggal_masuk' => '2026-06-22',
            'stok' => 0,
            'uom' => 'Unit',
            'lokasi_id' => $this->lokasi->id,
            'jabatan_id' => $this->jabatan->id,
            'stock_alert_enabled' => false,
        ]);

        $this->actingAs($this->admin)
            ->get('/dashboard')
            ->assertOk();

        $this->assertSame(0, $this->admin->notifications()->count());
        $this->assertDatabaseMissing('stock_alerts', [
            'alertable_type' => Inventory::class,
            'alertable_id' => $inventory->id,
        ]);

        $this->actingAs($this->admin)
            ->put('/inventory/' . $inventory->id . '/stock-alert', [
                'stock_alert_enabled' => 1,
            ])
            ->assertRedirect();

        $inventory->refresh();
        $this->assertTrue((bool) $inventory->stock_alert_enabled);
        $this->assertSame(1, $this->admin->notifications()->count());
        $this->assertDatabaseHas('stock_alerts', [
            'source' => 'inventory',
            'alertable_type' => Inventory::class,
            'alertable_id' => $inventory->id,
            'status' => 'empty',
        ]);
    }

    /** @test */
    public function disabling_stock_alert_resolves_active_alert_and_hides_unread_stock_notification()
    {
        $this->actingAs($this->admin)->post('/atk/store', [
            'nama_atk' => 'Spidol Board',
            'kategori' => 'Alat Tulis',
            'stok' => 2,
            'satuan' => 'Pcs',
            'lokasi' => 'Ruang Meeting',
            'active' => 1,
        ])->assertRedirect('/atk/1/detail');

        $atk = Atk::firstOrFail();
        $notification = $this->admin->notifications()->first();
        $this->assertNotNull($notification);
        $this->assertNull($notification->read_at);

        $this->actingAs($this->admin)
            ->put('/atk/' . $atk->id . '/stock-alert', [
                'stock_alert_enabled' => 0,
            ])
            ->assertRedirect();

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
        $this->assertDatabaseHas('stock_alerts', [
            'source' => 'atk',
            'alertable_type' => Atk::class,
            'alertable_id' => $atk->id,
            'status' => 'normal',
        ]);

        $this->actingAs($this->admin)
            ->getJson('/notifications/check-new?since=' . now()->subDay()->timestamp . '&include_existing_stock_alerts=1')
            ->assertOk()
            ->assertJsonPath('new_count', 0);
    }
}
