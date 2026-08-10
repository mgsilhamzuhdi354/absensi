<?php

namespace Tests\Feature;

use App\Models\Atk;
use App\Models\AssetTransfer;
use App\Models\AtkStockTransaction;
use App\Models\Company;
use App\Models\Jabatan;
use App\Models\settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class AtkManagementTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        settings::create([
            'name' => 'Absensi',
            'logo' => 'logo/absensi.png',
        ]);

        $jabatan = Jabatan::create([
            'nama_jabatan' => 'Admin Office',
        ]);

        $this->admin = User::create([
            'name' => 'Admin ATK',
            'username' => 'admin-atk',
            'email' => 'admin-atk@example.test',
            'is_admin' => 'admin',
            'jabatan_id' => $jabatan->id,
        ]);

        $this->user = User::create([
            'name' => 'Regular User',
            'username' => 'regular-user',
            'email' => 'regular-user@example.test',
            'is_admin' => 'user',
            'jabatan_id' => $jabatan->id,
        ]);
    }

    /** @test */
    public function non_admin_cannot_access_atk_management()
    {
        $this->actingAs($this->user)
            ->get('/atk')
            ->assertRedirect('/absen');

        $this->actingAs($this->user)
            ->get('/atk/scan')
            ->assertRedirect('/absen');
    }

    /** @test */
    public function admin_can_manage_atk_stock_data()
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->get('/atk/tambah')
            ->assertOk()
            ->assertSee('IOS/ATK/000001')
            ->assertSee('data-code-preview="CAB2/ATK/000001"', false);

        $this->actingAs($this->admin)
            ->get('/atk/tambah')
            ->assertOk()
            ->assertSee('IOS/ATK/000001');

        $this->assertDatabaseHas('counters', [
            'name' => 'ATK',
            'text' => 'ATK',
            'counter' => 0,
        ]);

        $this->actingAs($this->admin)->post('/atk/store', [
            'kode_atk' => 'MANUAL/999999',
            'nama_atk' => 'Pulpen Gel',
            'kategori' => 'Alat Tulis',
            'stok' => '25.5',
            'satuan' => 'Pcs',
            'lokasi' => 'Lemari Admin',
            'keterangan' => 'Warna hitam',
            'foto_barang' => UploadedFile::fake()->image('pulpen.jpg', 600, 400),
            'active' => '1',
            'warna_barang' => 'Hitam',
        ])->assertRedirect('/atk/1/detail');

        $atk = Atk::first();

        $this->assertNotNull($atk);
        $this->assertSame('Pulpen Gel', $atk->nama_atk);
        $this->assertSame('IOS/ATK/000001', $atk->kode_atk);
        $this->assertSame(25.5, $atk->stok);
        $this->assertSame(1, $atk->active);
        $this->assertTrue((bool) $atk->stock_alert_enabled);
        $this->assertDatabaseHas('atk_stock_variants', [
            'atk_id' => $atk->id,
            'warna_barang' => 'Hitam',
            'stok' => 25.5,
        ]);
        $this->assertNotEmpty($atk->qr_token);
        $this->assertNotEmpty($atk->qr_code_value);
        $this->assertNotEmpty($atk->qr_code_image);
        $this->assertNotEmpty($atk->foto_barang);
        Storage::disk('public')->assertExists($atk->qr_code_image);
        Storage::disk('public')->assertExists($atk->foto_barang);
        $photoPath = $atk->foto_barang;

        $this->actingAs($this->admin)
            ->get('/atk?search=Pulpen')
            ->assertOk()
            ->assertSee('Pulpen Gel')
            ->assertSee('IOS/ATK/000001')
            ->assertSee('Export Excel')
            ->assertSee('storage/' . $photoPath, false)
            ->assertSee('Hitam')
            ->assertSee('Notif aktif')
            ->assertSee('Ubah Stok');

        $this->actingAs($this->admin)
            ->get('/atk/' . $atk->id . '/detail')
            ->assertOk()
            ->assertSee('Detail ATK')
            ->assertSee('QR Code')
            ->assertSee('Pulpen Gel')
            ->assertSee('Stok Masuk')
            ->assertSee('Stok Keluar')
            ->assertSee('Stok Per Warna')
            ->assertSee('Hitam')
            ->assertSee('Notifikasi Stok')
            ->assertSee('Riwayat Stok Barang')
            ->assertSee('storage/' . $photoPath, false);

        foreach ([$atk->qr_token, $atk->qr_code_value, $atk->kode_atk, url('/atk/' . $atk->id . '/detail')] as $payload) {
            $this->actingAs($this->admin)
                ->getJson('/atk/scan/lookup?code=' . urlencode($payload))
                ->assertOk()
                ->assertJson([
                    'success' => true,
                    'url' => url('/atk/' . $atk->id . '/detail'),
                ]);
        }

        $this->actingAs($this->admin)
            ->get('/atk/scan')
            ->assertOk()
            ->assertSee('Scan ATK');

        $this->actingAs($this->admin)
            ->get('/atk/' . $atk->id . '/qr/print')
            ->assertOk()
            ->assertSee('QR ' . $atk->kode_atk);

        $this->actingAs($this->admin)
            ->get('/atk/' . $atk->id . '/qr/download')
            ->assertOk();

        $this->actingAs($this->admin)->post('/atk/' . $atk->id . '/stock-in', [
            'tanggal_transaksi' => '2026-06-19',
            'jumlah' => '4.5',
            'warna_barang' => 'Biru',
            'sumber_barang' => 'Supplier A',
            'catatan' => 'Pembelian tambahan',
        ])->assertRedirect('/atk/' . $atk->id . '/detail');

        $this->assertSame(30.0, (float) $atk->fresh()->stok);
        $this->assertDatabaseHas('atk_stock_transactions', [
            'atk_id' => $atk->id,
            'jenis_transaksi' => 'masuk',
            'warna_barang' => 'Biru',
            'stok_sebelum' => 25.5,
            'stok_sesudah' => 30,
            'diproses_oleh' => $this->admin->id,
        ]);
        $this->assertDatabaseHas('atk_stock_variants', [
            'atk_id' => $atk->id,
            'warna_barang' => 'Biru',
            'stok' => 4.5,
        ]);

        $this->actingAs($this->admin)->post('/atk/' . $atk->id . '/stock-out', [
            'tanggal_transaksi' => '2026-06-19',
            'jumlah' => '6',
            'warna_barang' => 'Hitam',
            'penerima_barang' => 'Ruang Finance',
            'catatan' => 'Dipakai divisi finance',
        ])->assertRedirect('/atk/' . $atk->id . '/detail');

        $this->assertSame(24.0, (float) $atk->fresh()->stok);
        $stockOutTransaction = AtkStockTransaction::where('jenis_transaksi', 'keluar')->first();
        $this->assertNotNull($stockOutTransaction);
        $this->assertDatabaseHas('atk_stock_transactions', [
            'id' => $stockOutTransaction->id,
            'atk_id' => $atk->id,
            'jumlah' => 6,
            'warna_barang' => 'Hitam',
            'penerima_barang' => 'Ruang Finance',
            'stok_sebelum' => 30,
            'stok_sesudah' => 24,
        ]);
        $this->assertDatabaseHas('atk_stock_variants', [
            'atk_id' => $atk->id,
            'warna_barang' => 'Hitam',
            'stok' => 19.5,
        ]);

        $this->actingAs($this->admin)->post('/atk/' . $atk->id . '/stock-out', [
            'tanggal_transaksi' => '2026-06-19',
            'jumlah' => '6',
            'warna_barang' => 'Biru',
            'penerima_barang' => 'Ruang Finance',
        ])->assertSessionHasErrors('warna_barang');

        $this->assertSame(24.0, (float) $atk->fresh()->stok);

        $this->actingAs($this->admin)->post('/atk/' . $atk->id . '/stock-out', [
            'tanggal_transaksi' => '2026-06-19',
            'jumlah' => '99',
            'warna_barang' => 'Hitam',
            'penerima_barang' => 'Ruang Finance',
        ])->assertSessionHasErrors('jumlah');

        $this->assertSame(24.0, (float) $atk->fresh()->stok);

        $this->actingAs($this->admin)
            ->delete('/atk/transactions/' . $stockOutTransaction->id)
            ->assertRedirect('/atk/' . $atk->id . '/detail');

        $this->assertSame(30.0, (float) $atk->fresh()->stok);
        $this->assertDatabaseHas('atk_stock_variants', [
            'atk_id' => $atk->id,
            'warna_barang' => 'Hitam',
            'stok' => 25.5,
        ]);
        $this->assertSoftDeleted('atk_stock_transactions', [
            'id' => $stockOutTransaction->id,
            'deleted_by' => $this->admin->id,
        ]);

        Excel::fake();

        $this->actingAs($this->admin)
            ->get('/atk/export?search=Pulpen&status=1')
            ->assertOk();

        Excel::assertDownloaded('Report ATK.xlsx');

        $this->actingAs($this->admin)->put('/atk/update/' . $atk->id, [
            'kode_atk' => 'MANUAL/000123',
            'nama_atk' => 'Pulpen Gel Hitam',
            'kategori' => 'Alat Tulis',
            'stok' => '12',
            'satuan' => 'Pcs',
            'lokasi' => 'Gudang ATK',
            'keterangan' => 'Stok tersisa',
            'active' => '0',
            'stock_alert_enabled' => '0',
        ])->assertRedirect('/atk/' . $atk->id . '/detail');

        $this->assertDatabaseHas('atks', [
            'id' => $atk->id,
            'kode_atk' => 'IOS/ATK/000001',
            'nama_atk' => 'Pulpen Gel Hitam',
            'stok' => 12,
            'lokasi' => 'Gudang ATK',
            'active' => 0,
            'stock_alert_enabled' => 0,
            'foto_barang' => $photoPath,
        ]);

        $this->actingAs($this->admin)
            ->delete('/atk/delete/' . $atk->id)
            ->assertRedirect('/atk');

        $this->assertSame(0, Atk::count());
        Storage::disk('public')->assertMissing($atk->qr_code_image);
        Storage::disk('public')->assertMissing($photoPath);
    }

    /** @test */
    public function admin_can_transfer_atk_stock_to_another_company()
    {
        Storage::fake('public');

        $targetCompany = Company::where('code', 'CAB2')->firstOrFail();

        $this->actingAs($this->admin)->post('/atk/store', [
            'nama_atk' => 'Kertas A4',
            'kategori' => 'Kertas',
            'stok' => '10',
            'satuan' => 'Rim',
            'lokasi' => 'Gudang Pusat',
            'active' => '1',
            'warna_barang' => 'Putih',
        ])->assertRedirect('/atk/1/detail');

        $source = Atk::firstOrFail();

        $response = $this->actingAs($this->admin)->post('/atk/' . $source->id . '/transfer-company', [
            'destination_company_id' => $targetCompany->id,
            'tanggal_transfer' => '2026-08-10',
            'jumlah' => '3',
            'warna_barang' => 'Putih',
            'catatan' => 'Pembukaan stok cabang',
        ]);

        $target = Atk::withoutGlobalScope('company')
            ->where('company_id', $targetCompany->id)
            ->where('id', '!=', $source->id)
            ->firstOrFail();

        $response->assertRedirect('/atk/' . $target->id . '/detail');

        $this->assertSame(7.0, (float) $source->fresh()->stok);
        $this->assertSame(3.0, (float) $target->stok);
        $this->assertSame('CAB2/ATK/000001', $target->kode_atk);

        $this->assertDatabaseHas('atk_stock_transactions', [
            'atk_id' => $source->id,
            'company_id' => $source->company_id,
            'jenis_transaksi' => 'keluar',
            'jumlah' => 3,
            'penerima_barang' => $targetCompany->name,
        ]);

        $this->assertDatabaseHas('atk_stock_transactions', [
            'atk_id' => $target->id,
            'company_id' => $targetCompany->id,
            'jenis_transaksi' => 'masuk',
            'jumlah' => 3,
            'sumber_barang' => 'PT Indoocean Crew Service',
        ]);

        $this->assertDatabaseHas('asset_transfers', [
            'transferable_type' => Atk::class,
            'transferable_id' => $source->id,
            'target_transferable_id' => $target->id,
            'source_company_id' => $source->company_id,
            'destination_company_id' => $targetCompany->id,
            'jumlah' => 3,
            'warna_barang' => 'Putih',
        ]);

        $this->assertSame(1, AssetTransfer::withoutGlobalScope('company')->count());

        $this->actingAs($this->admin)
            ->get('/atk/' . $target->id . '/detail')
            ->assertOk()
            ->assertSee('Transfer Perusahaan')
            ->assertSee('Riwayat Transfer Perusahaan')
            ->assertSee('Kertas A4');
    }

    /** @test */
    public function storing_atk_for_selected_company_switches_admin_context_to_that_company()
    {
        Storage::fake('public');

        $targetCompany = Company::where('code', 'CAB2')->firstOrFail();

        $response = $this->actingAs($this->admin)->post('/atk/store', [
            'company_id' => $targetCompany->id,
            'nama_atk' => 'Sticky Notes Cabang',
            'kategori' => 'Office',
            'stok' => '5',
            'satuan' => 'Pack',
            'lokasi' => 'Gudang Cabang',
            'active' => '1',
            'warna_barang' => 'Umum',
        ]);

        $atk = Atk::withoutGlobalScope('company')->firstOrFail();

        $response->assertRedirect('/atk/' . $atk->id . '/detail');
        $this->assertSame($targetCompany->id, session('active_company_id'));
        $this->assertSame('CAB2/ATK/000001', $atk->kode_atk);

        $this->actingAs($this->admin)
            ->get('/atk')
            ->assertOk()
            ->assertSee('Sticky Notes Cabang');
    }
}
