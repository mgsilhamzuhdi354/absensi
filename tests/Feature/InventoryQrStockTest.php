<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryBastDocument;
use App\Models\InventoryStockTransaction;
use App\Models\Jabatan;
use App\Models\Lokasi;
use App\Models\settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InventoryQrStockTest extends TestCase
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
            'nama_jabatan' => 'IT Engineer',
        ]);

        $this->lokasi = Lokasi::create([
            'nama_lokasi' => 'Gudang IT',
        ]);

        $this->admin = User::create([
            'name' => 'Admin IT',
            'username' => 'admin-it',
            'email' => 'admin-it@example.test',
            'is_admin' => 'admin',
            'jabatan_id' => $this->jabatan->id,
        ]);

        $this->employee = User::create([
            'name' => 'Employee One',
            'username' => 'employee-one',
            'email' => 'employee-one@example.test',
            'is_admin' => 'user',
            'jabatan_id' => $this->jabatan->id,
        ]);
    }

    /** @test */
    public function admin_can_store_inventory_and_qr_is_generated()
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)->post('/inventory/store', $this->inventoryPayload());

        $inventory = Inventory::first();

        $response->assertRedirect('/inventory/' . $inventory->id . '/detail');
        $this->assertNotNull($inventory->qr_token);
        $this->assertStringContainsString('/inventory/scan/lookup?code=', $inventory->qr_code_value);
        Storage::disk('public')->assertExists($inventory->qr_code_image);
    }

    /** @test */
    public function scan_lookup_returns_detail_url_for_qr_token()
    {
        $inventory = Inventory::create($this->inventoryPayload([
            'qr_token' => 'inventory-token-123',
        ]));

        $response = $this->actingAs($this->admin)
            ->getJson('/inventory/scan/lookup?code=inventory-token-123');

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'url' => url('/inventory/' . $inventory->id . '/detail'),
            ]);
    }

    /** @test */
    public function scan_lookup_accepts_common_qr_payload_formats()
    {
        $inventory = Inventory::create($this->inventoryPayload([
            'qr_token' => 'inventory-token-123',
            'qr_code_value' => 'http://127.0.0.1:8000/inventory/scan/lookup?code=inventory-token-123',
        ]));

        $expectedUrl = url('/inventory/' . $inventory->id . '/detail');
        $payloads = [
            'http://127.0.0.1:8000/inventory/scan/lookup?code=inventory-token-123',
            url('/inventory/' . $inventory->id . '/detail'),
            (string) $inventory->id,
            'INV-000001',
        ];

        foreach ($payloads as $payload) {
            $this->actingAs($this->admin)
                ->getJson('/inventory/scan/lookup?code=' . urlencode($payload))
                ->assertOk()
                ->assertJson([
                    'success' => true,
                    'url' => $expectedUrl,
                ]);
        }
    }

    /** @test */
    public function detail_and_scan_pages_can_render()
    {
        Storage::fake('public');
        $inventory = Inventory::create($this->inventoryPayload());

        $this->actingAs($this->admin)
            ->get('/inventory/' . $inventory->id . '/detail')
            ->assertOk()
            ->assertSee('QR Code')
            ->assertSee('Stok Masuk')
            ->assertSee('Stok Keluar');

        $this->actingAs($this->admin)
            ->get('/inventory/scan')
            ->assertOk()
            ->assertSee('Scan Barang');
    }

    /** @test */
    public function stock_in_increases_stock_and_records_history()
    {
        $inventory = Inventory::create($this->inventoryPayload(['stok' => 10]));

        $response = $this->actingAs($this->admin)->post('/inventory/' . $inventory->id . '/stock-in', [
            'tanggal_transaksi' => '2026-05-29',
            'jumlah' => 2.5,
            'sumber_barang' => 'Supplier A',
            'kondisi_barang' => 'Baik',
            'lokasi_id' => $this->lokasi->id,
            'catatan' => 'Barang tambahan',
        ]);

        $response->assertRedirect('/inventory/' . $inventory->id . '/detail');
        $this->assertSame(12.5, (float) $inventory->fresh()->stok);
        $this->assertDatabaseHas('inventory_stock_transactions', [
            'inventory_id' => $inventory->id,
            'jenis_transaksi' => 'masuk',
            'stok_sebelum' => 10,
            'stok_sesudah' => 12.5,
            'diproses_oleh' => $this->admin->id,
        ]);
    }

    /** @test */
    public function stock_out_decreases_stock_and_records_receiver()
    {
        $inventory = Inventory::create($this->inventoryPayload(['stok' => 10]));

        $response = $this->actingAs($this->admin)->post('/inventory/' . $inventory->id . '/stock-out', [
            'tanggal_transaksi' => '2026-05-29',
            'jumlah' => 3,
            'penerima_user_id' => $this->employee->id,
            'keperluan' => 'Fasilitas kerja',
            'kondisi_barang' => 'Baik',
            'catatan' => 'Diserahkan ke karyawan',
        ]);

        $response->assertRedirect('/inventory/' . $inventory->id . '/detail');
        $this->assertSame(7.0, (float) $inventory->fresh()->stok);
        $this->assertDatabaseHas('inventory_stock_transactions', [
            'inventory_id' => $inventory->id,
            'jenis_transaksi' => 'keluar',
            'jumlah' => 3,
            'penerima_user_id' => $this->employee->id,
            'penerima_barang' => $this->employee->name,
            'diproses_oleh' => $this->admin->id,
        ]);
    }

    /** @test */
    public function selected_employee_data_overrides_manual_receiver_fields_on_stock_out()
    {
        $inventory = Inventory::create($this->inventoryPayload(['stok' => 10]));

        $response = $this->actingAs($this->admin)->post('/inventory/' . $inventory->id . '/stock-out', [
            'tanggal_transaksi' => '2026-05-29',
            'jumlah' => 1,
            'penerima_user_id' => $this->employee->id,
            'penerima_barang' => 'Nama Salah',
            'jabatan_penerima' => 'Jabatan Salah',
            'departemen_penerima' => 'Departemen Salah',
            'buat_bast_otomatis' => 0,
        ]);

        $response->assertRedirect('/inventory/' . $inventory->id . '/detail');
        $this->assertDatabaseHas('inventory_stock_transactions', [
            'inventory_id' => $inventory->id,
            'jenis_transaksi' => 'keluar',
            'penerima_user_id' => $this->employee->id,
            'penerima_barang' => $this->employee->name,
            'jabatan_penerima' => $this->jabatan->nama_jabatan,
            'departemen_penerima' => $this->jabatan->nama_jabatan,
        ]);
    }

    /** @test */
    public function stock_out_cannot_exceed_available_stock()
    {
        $inventory = Inventory::create($this->inventoryPayload(['stok' => 2]));

        $response = $this->actingAs($this->admin)->post('/inventory/' . $inventory->id . '/stock-out', [
            'tanggal_transaksi' => '2026-05-29',
            'jumlah' => 3,
            'penerima_barang' => 'Manual Receiver',
        ]);

        $response->assertSessionHasErrors('jumlah');
        $this->assertSame(2.0, (float) $inventory->fresh()->stok);
        $this->assertSame(0, InventoryStockTransaction::count());
    }

    /** @test */
    public function admin_can_soft_delete_stock_transaction_and_deleted_by_is_tracked()
    {
        $inventory = Inventory::create($this->inventoryPayload(['stok' => 7]));
        $transaction = InventoryStockTransaction::create([
            'inventory_id' => $inventory->id,
            'jenis_transaksi' => 'keluar',
            'jumlah' => 3,
            'stok_sebelum' => 10,
            'stok_sesudah' => 7,
            'tanggal_transaksi' => '2026-05-29',
            'penerima_barang' => $this->employee->name,
            'diproses_oleh' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->delete('/inventory/transactions/' . $transaction->id);

        $response->assertRedirect('/inventory/' . $inventory->id . '/detail');
        $this->assertSame(10.0, (float) $inventory->fresh()->stok);
        $this->assertSoftDeleted('inventory_stock_transactions', [
            'id' => $transaction->id,
            'deleted_by' => $this->admin->id,
        ]);
        $this->assertSame(0, InventoryStockTransaction::count());
        $this->assertSame(1, InventoryStockTransaction::onlyTrashed()->count());
    }

    /** @test */
    public function bast_can_be_created_for_stock_out_transaction()
    {
        Storage::fake('public');

        $inventory = Inventory::create($this->inventoryPayload(['stok' => 1]));
        $transaction = InventoryStockTransaction::create([
            'inventory_id' => $inventory->id,
            'jenis_transaksi' => 'keluar',
            'jumlah' => 1,
            'stok_sebelum' => 1,
            'stok_sesudah' => 0,
            'tanggal_transaksi' => '2026-05-29',
            'penerima_barang' => $this->employee->name,
            'jabatan_penerima' => 'IT Engineer',
            'kondisi_barang' => 'Baik',
            'diproses_oleh' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->post('/inventory/transactions/' . $transaction->id . '/bast', [
            'tanggal_surat' => '2026-05-29',
            'nama_mengetahui' => 'HRD Manager',
        ]);

        $response->assertRedirect('/inventory/' . $inventory->id . '/detail');
        $document = InventoryBastDocument::first();
        $this->assertSame('001 / IT-BAST / V / 2026', $document->nomor_surat);
        Storage::disk('public')->assertExists($document->file_pdf);
    }

    /** @test */
    public function updating_inventory_refreshes_related_handover_history_and_bast_file()
    {
        Storage::fake('public');
        $newJabatan = Jabatan::create([
            'nama_jabatan' => 'Teknologi Informasi',
        ]);

        $inventory = Inventory::create($this->inventoryPayload([
            'stok' => 1,
            'kondisi' => 'Baik',
        ]));
        $transaction = InventoryStockTransaction::create([
            'inventory_id' => $inventory->id,
            'jenis_transaksi' => 'keluar',
            'jumlah' => 1,
            'stok_sebelum' => 1,
            'stok_sesudah' => 0,
            'tanggal_transaksi' => '2026-05-29',
            'penerima_barang' => $this->employee->name,
            'jabatan_penerima' => 'IT Engineer',
            'kondisi_barang' => 'Baik',
            'diproses_oleh' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post('/inventory/transactions/' . $transaction->id . '/bast', [
            'tanggal_surat' => '2026-05-29',
            'nama_mengetahui' => 'HRD Manager',
        ])->assertRedirect('/inventory/' . $inventory->id . '/detail');

        $document = InventoryBastDocument::first();
        $oldPdfContent = Storage::disk('public')->get($document->file_pdf);

        $this->actingAs($this->admin)->put('/inventory/update/' . $inventory->id, $this->inventoryPayload([
            'kode_barang' => $inventory->kode_barang,
            'nama_barang' => 'Laptop Dell Updated',
            'merk_tipe' => 'Dell Latitude 7290',
            'serial_number' => 'SN999',
            'spesifikasi' => 'RAM 16GB, SSD 512GB',
            'kondisi' => 'Maintenance',
            'stok' => 0,
            'jabatan_id' => $newJabatan->id,
        ]))->assertRedirect('/inventory/' . $inventory->id . '/detail');

        $this->assertDatabaseHas('inventory_stock_transactions', [
            'id' => $transaction->id,
            'kondisi_barang' => 'Maintenance',
        ]);

        $document->refresh();
        $this->assertSame('Teknologi Informasi', $document->jabatan_penyerah);
        Storage::disk('public')->assertExists($document->file_pdf);
        $this->assertNotSame($oldPdfContent, Storage::disk('public')->get($document->file_pdf));

        $transaction->refresh();
        $transaction->load('inventory.jabatan', 'processedBy');
        $html = view('inventory.bast_pdf', [
            'document' => $document,
            'transaction' => $transaction,
            'inventory' => $transaction->inventory,
        ])->render();

        $this->assertStringContainsString('Jabatan</td>', $html);
        $this->assertStringContainsString(': Teknologi Informasi', $html);
        $this->assertStringNotContainsString(': Administrasi &amp; Umum', $html);
    }

    private function inventoryPayload(array $override = [])
    {
        return array_merge([
            'kode_barang' => 'INV/000001',
            'nama_barang' => 'Laptop Dell',
            'jenis_barang' => 'Laptop',
            'merk_tipe' => 'Dell Latitude 7280',
            'serial_number' => 'SN123',
            'spesifikasi' => 'RAM 8GB, SSD 256GB',
            'kondisi' => 'Baik',
            'status_barang' => 'Aktif',
            'tanggal_masuk' => '2026-05-29',
            'stok' => 1,
            'uom' => 'Unit',
            'desc' => 'Laptop operasional',
            'lokasi_id' => $this->lokasi->id,
            'jabatan_id' => $this->jabatan->id,
        ], $override);
    }
}
