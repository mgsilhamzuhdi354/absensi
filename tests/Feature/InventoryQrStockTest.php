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
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class InventoryQrStockTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $employee;
    private $hrd;
    private $firstParty;
    private $jabatan;
    private $hrdJabatan;
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

        $this->hrdJabatan = Jabatan::create([
            'nama_jabatan' => 'HRD / Manager',
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

        $this->hrd = User::create([
            'name' => 'HRD One',
            'username' => 'hrd-one',
            'email' => 'hrd-one@example.test',
            'is_admin' => 'user',
            'jabatan_id' => $this->hrdJabatan->id,
        ]);

        $this->firstParty = User::create([
            'name' => 'IT PIC One',
            'username' => 'it-pic-one',
            'email' => 'it-pic-one@example.test',
            'is_admin' => 'user',
            'jabatan_id' => $this->jabatan->id,
        ]);
    }

    /** @test */
    public function admin_can_store_inventory_and_qr_is_generated()
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->get('/inventory/tambah')
            ->assertOk()
            ->assertSee('INV/000001');

        $this->actingAs($this->admin)
            ->get('/inventory/tambah')
            ->assertOk()
            ->assertSee('INV/000001');

        $this->assertDatabaseHas('counters', [
            'name' => 'Inventory',
            'text' => 'INV',
            'counter' => 0,
        ]);

        $response = $this->actingAs($this->admin)->post('/inventory/store', $this->inventoryPayload([
            'kode_barang' => 'MANUAL/999999',
        ]));

        $inventory = Inventory::first();

        $response->assertRedirect('/inventory/' . $inventory->id . '/detail');
        $this->assertSame('INV/000001', $inventory->kode_barang);
        $this->assertNotNull($inventory->qr_token);
        $this->assertStringContainsString('/inventory/scan/lookup?code=', $inventory->qr_code_value);
        Storage::disk('public')->assertExists($inventory->qr_code_image);

        $this->actingAs($this->admin)->post('/inventory/store', $this->inventoryPayload([
            'kode_barang' => 'MANUAL/888888',
            'nama_barang' => 'Laptop Dell Cadangan',
            'serial_number' => 'SN124',
        ]))->assertRedirect('/inventory/2/detail');

        $this->assertSame('INV/000002', Inventory::find(2)->kode_barang);
    }

    /** @test */
    public function admin_can_store_inventory_photo_and_detail_displays_it()
    {
        Storage::fake('public');

        $response = $this->actingAs($this->admin)->post('/inventory/store', $this->inventoryPayload([
            'foto_barang' => UploadedFile::fake()->image('laptop.jpg', 600, 400),
        ]));

        $inventory = Inventory::first();

        $response->assertRedirect('/inventory/' . $inventory->id . '/detail');
        $this->assertNotNull($inventory->foto_barang);
        $this->assertDatabaseHas('inventories', [
            'id' => $inventory->id,
            'foto_barang' => $inventory->foto_barang,
        ]);
        Storage::disk('public')->assertExists($inventory->foto_barang);

        $this->actingAs($this->admin)
            ->get('/inventory/' . $inventory->id . '/detail')
            ->assertOk()
            ->assertSee('storage/' . $inventory->foto_barang, false);
    }

    /** @test */
    public function numeric_uom_is_rejected_so_stock_and_unit_do_not_get_mixed()
    {
        $response = $this->actingAs($this->admin)->post('/inventory/store', $this->inventoryPayload([
            'uom' => '11',
        ]));

        $response->assertSessionHasErrors('uom');
        $this->assertSame(0, Inventory::count());
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
            ->assertSee('Scan Aset Kantor');
    }

    /** @test */
    public function detail_page_still_renders_when_return_bast_tables_are_missing()
    {
        Storage::fake('public');

        Schema::dropIfExists('inventory_return_documents');

        $inventory = Inventory::create($this->inventoryPayload(['stok' => 1]));
        InventoryStockTransaction::create([
            'inventory_id' => $inventory->id,
            'jenis_transaksi' => 'masuk',
            'jumlah' => 1,
            'stok_sebelum' => 0,
            'stok_sesudah' => 1,
            'tanggal_transaksi' => '2026-06-15',
            'sumber_barang' => 'Pengembalian dari pegawai',
            'diproses_oleh' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get('/inventory/' . $inventory->id . '/detail')
            ->assertOk()
            ->assertSee('Riwayat Stok Barang');
    }

    /** @test */
    public function stock_in_increases_stock_and_records_history()
    {
        $inventory = Inventory::create($this->inventoryPayload(['stok' => 10, 'uom' => 'Kg']));

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
    public function stock_in_rejects_fractional_quantity_for_unit_items()
    {
        $inventory = Inventory::create($this->inventoryPayload(['stok' => 1, 'uom' => 'Unit']));

        $response = $this->actingAs($this->admin)->post('/inventory/' . $inventory->id . '/stock-in', [
            'tanggal_transaksi' => '2026-05-29',
            'jumlah' => 0.95,
            'sumber_barang' => 'Supplier A',
            'kondisi_barang' => 'Baik',
        ]);

        $response->assertSessionHasErrors('jumlah');
        $this->assertSame(1.0, (float) $inventory->fresh()->stok);
        $this->assertSame(0, InventoryStockTransaction::count());
    }

    /** @test */
    public function initial_stock_must_be_whole_number_for_unit_items()
    {
        $response = $this->actingAs($this->admin)->post('/inventory/store', $this->inventoryPayload([
            'stok' => 1.95,
            'uom' => 'Unit',
        ]));

        $response->assertSessionHasErrors('stok');
        $this->assertSame(0, Inventory::count());
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
    public function legacy_fractional_unit_stock_is_treated_as_whole_stock_for_handover()
    {
        $inventory = Inventory::create($this->inventoryPayload(['stok' => 1.95, 'uom' => 'Unit']));

        $this->actingAs($this->admin)
            ->get('/inventory/' . $inventory->id . '/detail')
            ->assertOk()
            ->assertSee('<strong>2</strong>', false)
            ->assertSee('max="2"', false);

        $response = $this->actingAs($this->admin)->post('/inventory/' . $inventory->id . '/stock-out', [
            'tanggal_transaksi' => '2026-05-29',
            'jumlah' => 2,
            'penerima_user_id' => $this->employee->id,
            'keperluan' => 'Fasilitas kerja',
            'kondisi_barang' => 'Baik',
            'buat_bast_otomatis' => 0,
        ]);

        $response->assertRedirect('/inventory/' . $inventory->id . '/detail');
        $this->assertSame(0.0, (float) $inventory->fresh()->stok);
        $this->assertDatabaseHas('inventory_stock_transactions', [
            'inventory_id' => $inventory->id,
            'jenis_transaksi' => 'keluar',
            'jumlah' => 2,
            'stok_sebelum' => 2,
            'stok_sesudah' => 0,
        ]);
    }

    /** @test */
    public function stock_out_rejects_fractional_handover_quantity()
    {
        $inventory = Inventory::create($this->inventoryPayload(['stok' => 1]));

        $response = $this->actingAs($this->admin)->post('/inventory/' . $inventory->id . '/stock-out', [
            'tanggal_transaksi' => '2026-05-29',
            'jumlah' => 0.05,
            'penerima_user_id' => $this->employee->id,
            'buat_bast_otomatis' => 0,
        ]);

        $response->assertSessionHasErrors('jumlah');
        $this->assertSame(1.0, (float) $inventory->fresh()->stok);
        $this->assertSame(0, InventoryStockTransaction::count());
    }

    /** @test */
    public function detail_shows_current_inventory_holder_after_handover()
    {
        $inventory = Inventory::create($this->inventoryPayload(['stok' => 1]));

        $this->actingAs($this->admin)->post('/inventory/' . $inventory->id . '/stock-out', [
            'tanggal_transaksi' => '2026-05-29',
            'jumlah' => 1,
            'penerima_user_id' => $this->employee->id,
            'keperluan' => 'Fasilitas kerja',
            'kondisi_barang' => 'Baik',
            'buat_bast_otomatis' => 0,
        ])->assertRedirect('/inventory/' . $inventory->id . '/detail');

        $this->actingAs($this->admin)
            ->get('/inventory/' . $inventory->id . '/detail')
            ->assertOk()
            ->assertSee('Pemegang Saat Ini')
            ->assertSee($this->employee->name)
            ->assertSee('Sejak 29/05/2026');
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
    public function deleting_inventory_is_safe_for_stale_or_repeated_requests()
    {
        Storage::fake('public');

        $inventory = Inventory::create($this->inventoryPayload());

        $this->actingAs($this->admin)
            ->get('/inventory/delete/' . $inventory->id)
            ->assertRedirect('/inventory');

        $this->actingAs($this->admin)
            ->delete('/inventory/delete/' . $inventory->id)
            ->assertRedirect('/inventory');

        $this->assertDatabaseMissing('inventories', [
            'id' => $inventory->id,
        ]);

        $this->actingAs($this->admin)
            ->delete('/inventory/delete/' . $inventory->id)
            ->assertRedirect('/inventory');

        $this->actingAs($this->admin)
            ->get('/inventory/' . $inventory->id . '/detail')
            ->assertRedirect('/inventory');
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
    public function assigned_signers_get_bast_notifications_and_can_sign_their_roles()
    {
        Storage::fake('public');

        $inventory = Inventory::create($this->inventoryPayload(['stok' => 1]));

        $response = $this->actingAs($this->admin)->post('/inventory/' . $inventory->id . '/stock-out', [
            'tanggal_transaksi' => '2026-05-29',
            'jumlah' => 1,
            'penerima_user_id' => $this->employee->id,
            'keperluan' => 'Fasilitas kerja',
            'kondisi_barang' => 'Baik',
            'buat_bast_otomatis' => 1,
            'known_by_user_id' => $this->hrd->id,
            'first_party_user_id' => $this->firstParty->id,
        ]);

        $response->assertRedirect('/inventory/' . $inventory->id . '/detail');

        $document = InventoryBastDocument::first();
        $this->assertNotNull($document);
        $this->assertSame($this->hrd->id, $document->known_by_user_id);
        $this->assertSame($this->firstParty->id, $document->first_party_user_id);
        $this->assertSame($this->hrd->name, $document->nama_mengetahui);
        $this->assertSame($this->firstParty->name, $document->nama_penyerah);

        foreach ([$this->employee, $this->hrd, $this->firstParty] as $signer) {
            $notification = $signer->notifications()->first();
            $this->assertNotNull($notification);
            $this->assertSame('/my-inventory-bast/' . $document->id, $notification->data['action']);
            $this->assertSame($document->id, $notification->data['bast_document_id']);
        }

        $this->actingAs($this->employee)
            ->get('/my-inventory-bast')
            ->assertOk()
            ->assertSee($document->nomor_surat)
            ->assertSee('Menunggu TTD');

        $this->actingAs($this->employee)
            ->get('/my-inventory-bast/' . $document->id)
            ->assertOk()
            ->assertSee('PIHAK KEDUA')
            ->assertSee('Tanda Tangani');

        $this->actingAs($this->employee)
            ->post('/my-inventory-bast/' . $document->id . '/sign/receiver', [
                'agreement' => 1,
                'signature_data' => $this->signatureData(),
            ])
            ->assertRedirect('/my-inventory-bast/' . $document->id);

        $this->actingAs($this->hrd)
            ->get('/my-inventory-bast/' . $document->id)
            ->assertOk()
            ->assertSee('MENGETAHUI')
            ->assertSee('Tanda Tangani');

        $this->actingAs($this->hrd)
            ->post('/my-inventory-bast/' . $document->id . '/sign/known', [
                'agreement' => 1,
                'signature_data' => $this->signatureData(),
            ])
            ->assertRedirect('/my-inventory-bast/' . $document->id);

        $this->actingAs($this->firstParty)
            ->get('/my-inventory-bast/' . $document->id)
            ->assertOk()
            ->assertSee('PIHAK PERTAMA')
            ->assertSee('Tanda Tangani');

        $this->actingAs($this->firstParty)
            ->post('/my-inventory-bast/' . $document->id . '/sign/first_party', [
                'agreement' => 1,
                'signature_data' => $this->signatureData(),
            ])
            ->assertRedirect('/my-inventory-bast/' . $document->id);

        $document->refresh();
        $this->assertSame($this->employee->id, $document->signed_by_user_id);
        $this->assertSame($this->employee->name, $document->receiver_signature_name);
        $this->assertNotNull($document->signed_at);
        $this->assertSame($this->hrd->id, $document->known_by_user_id);
        $this->assertSame($this->hrd->name, $document->known_signature_name);
        $this->assertNotNull($document->known_signed_at);
        $this->assertSame($this->firstParty->id, $document->first_party_user_id);
        $this->assertSame($this->firstParty->name, $document->first_party_signature_name);
        $this->assertNotNull($document->first_party_signed_at);
        Storage::disk('public')->assertExists($document->receiver_signature_image);
        Storage::disk('public')->assertExists($document->known_signature_image);
        Storage::disk('public')->assertExists($document->first_party_signature_image);
        Storage::disk('public')->assertExists($document->file_pdf);

        $transaction = InventoryStockTransaction::with('inventory.jabatan', 'processedBy', 'penerima.Jabatan')->first();
        $document->load('knownBy.Jabatan', 'firstParty.Jabatan', 'signedBy.Jabatan');
        $html = view('inventory.bast_pdf', [
            'document' => $document,
            'transaction' => $transaction,
            'inventory' => $transaction->inventory,
        ])->render();

        $this->assertStringContainsString('Terverifikasi elektronik', $html);
        $this->assertStringContainsString($this->employee->name, $html);
        $this->assertStringContainsString($this->hrd->name, $html);
        $this->assertStringContainsString($this->firstParty->name, $html);
    }

    /** @test */
    public function non_receiver_cannot_open_or_sign_another_users_bast()
    {
        Storage::fake('public');

        $otherUser = User::create([
            'name' => 'Other User',
            'username' => 'other-user',
            'email' => 'other-user@example.test',
            'is_admin' => 'user',
            'jabatan_id' => $this->jabatan->id,
        ]);

        $inventory = Inventory::create($this->inventoryPayload(['stok' => 1]));
        $transaction = InventoryStockTransaction::create([
            'inventory_id' => $inventory->id,
            'jenis_transaksi' => 'keluar',
            'jumlah' => 1,
            'stok_sebelum' => 1,
            'stok_sesudah' => 0,
            'tanggal_transaksi' => '2026-05-29',
            'penerima_user_id' => $this->employee->id,
            'penerima_barang' => $this->employee->name,
            'jabatan_penerima' => $this->jabatan->nama_jabatan,
            'kondisi_barang' => 'Baik',
            'diproses_oleh' => $this->admin->id,
        ]);

        $document = $this->actingAs($this->admin)
            ->post('/inventory/transactions/' . $transaction->id . '/bast', [
                'tanggal_surat' => '2026-05-29',
            ]);

        $document->assertRedirect('/inventory/' . $inventory->id . '/detail');
        $bast = InventoryBastDocument::first();

        $this->actingAs($otherUser)
            ->get('/my-inventory-bast/' . $bast->id)
            ->assertNotFound();

        $this->actingAs($otherUser)
            ->post('/my-inventory-bast/' . $bast->id . '/sign', [
                'agreement' => 1,
            ])
            ->assertNotFound();

        $this->assertNull($bast->fresh()->signed_at);
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

    /** @test */
    public function updating_employee_position_refreshes_receiver_position_in_inventory_bast()
    {
        Storage::fake('public');
        $newJabatan = Jabatan::create([
            'nama_jabatan' => 'Keuangan dan Akutansi',
        ]);

        $inventory = Inventory::create($this->inventoryPayload(['stok' => 1]));
        $transaction = InventoryStockTransaction::create([
            'inventory_id' => $inventory->id,
            'jenis_transaksi' => 'keluar',
            'jumlah' => 1,
            'stok_sebelum' => 1,
            'stok_sesudah' => 0,
            'tanggal_transaksi' => '2026-06-04',
            'penerima_user_id' => $this->employee->id,
            'penerima_barang' => $this->employee->name,
            'jabatan_penerima' => 'IT Engineer',
            'kondisi_barang' => 'Baik',
            'diproses_oleh' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post('/inventory/transactions/' . $transaction->id . '/bast', [
            'tanggal_surat' => '2026-06-04',
            'known_by_user_id' => $this->hrd->id,
            'first_party_user_id' => $this->firstParty->id,
        ])->assertRedirect('/inventory/' . $inventory->id . '/detail');

        $document = InventoryBastDocument::first();
        $this->assertSame('IT Engineer', $document->jabatan_penerima);

        $this->actingAs($this->admin)->put('/pegawai/proses-edit/' . $this->employee->id, [
            'name' => $this->employee->name,
            'email' => $this->employee->email,
            'username' => $this->employee->username,
            'telepon' => '08123456789',
            'lokasi_id' => $this->lokasi->id,
            'tgl_lahir' => '2001-04-21',
            'tgl_join' => '2025-12-15',
            'gender' => 'Laki-Laki',
            'is_admin' => 'user',
            'status_nikah' => 'TK/0',
            'jabatan_id' => $newJabatan->id,
        ])->assertRedirect('/pegawai');

        $document->refresh();
        $this->assertSame('Keuangan dan Akutansi', $document->jabatan_penerima);
        Storage::disk('public')->assertExists($document->file_pdf);

        $transaction->refresh();
        $transaction->load('inventory.jabatan', 'processedBy', 'penerima.Jabatan');
        $html = view('inventory.bast_pdf', [
            'document' => $document,
            'transaction' => $transaction,
            'inventory' => $transaction->inventory,
        ])->render();

        $this->assertStringContainsString(': Keuangan dan Akutansi', $html);
        $this->assertStringContainsString('<div class="signature-position">Keuangan dan Akutansi</div>', $html);
    }

    /** @test */
    public function admin_can_edit_bast_party_position_and_department_from_inventory_detail()
    {
        Storage::fake('public');

        $inventory = Inventory::create($this->inventoryPayload(['stok' => 1]));
        $transaction = InventoryStockTransaction::create([
            'inventory_id' => $inventory->id,
            'jenis_transaksi' => 'keluar',
            'jumlah' => 1,
            'stok_sebelum' => 1,
            'stok_sesudah' => 0,
            'tanggal_transaksi' => '2026-06-04',
            'penerima_user_id' => $this->employee->id,
            'penerima_barang' => $this->employee->name,
            'jabatan_penerima' => 'IT Engineer',
            'departemen_penerima' => 'IT Engineer',
            'kondisi_barang' => 'Baik',
            'diproses_oleh' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post('/inventory/transactions/' . $transaction->id . '/bast', [
            'tanggal_surat' => '2026-06-04',
            'known_by_user_id' => $this->hrd->id,
            'first_party_user_id' => $this->firstParty->id,
        ])->assertRedirect('/inventory/' . $inventory->id . '/detail');

        $document = InventoryBastDocument::first();

        $this->actingAs($this->admin)->put('/inventory/bast/' . $document->id, [
            'tanggal_surat' => '2026-06-04',
            'nama_penyerah' => 'Mgs Ilham Zuhdi',
            'jabatan_penyerah' => 'IT Support',
            'departemen_penyerah' => 'Teknologi Informasi',
            'nama_penerima' => 'Budhy Krisna Akbar',
            'jabatan_penerima' => 'Staff Finance',
            'departemen_penerima' => 'Keuangan dan Akutansi',
            'nama_mengetahui' => 'HRD One',
        ])->assertRedirect('/inventory/' . $inventory->id . '/detail');

        $document->refresh();
        $this->assertTrue((bool) $document->party_details_locked);
        $this->assertSame('IT Support', $document->jabatan_penyerah);
        $this->assertSame('Staff Finance', $document->jabatan_penerima);
        $this->assertSame('Keuangan dan Akutansi', $document->departemen_penerima);
        $this->assertSame('Teknologi Informasi', $document->departemen_penyerah);

        $transaction->refresh();
        $this->assertSame('Staff Finance', $transaction->jabatan_penerima);
        $this->assertSame('Keuangan dan Akutansi', $transaction->departemen_penerima);

        $transaction->load('inventory.jabatan', 'processedBy', 'penerima.Jabatan');
        $html = view('inventory.bast_pdf', [
            'document' => $document,
            'transaction' => $transaction,
            'inventory' => $transaction->inventory,
        ])->render();

        $this->assertStringContainsString(': IT Support', $html);
        $this->assertStringContainsString(': Staff Finance', $html);
        $this->assertStringContainsString(': Keuangan dan Akutansi', $html);
        $this->assertStringContainsString(': Teknologi Informasi', $html);
        Storage::disk('public')->assertExists($document->file_pdf);
    }

    private function signatureData()
    {
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=';
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
