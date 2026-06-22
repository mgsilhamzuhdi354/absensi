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
}
