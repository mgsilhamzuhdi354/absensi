<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\InventoryReturnDocument;
use App\Models\InventoryStockTransaction;
use App\Models\Jabatan;
use App\Models\Lokasi;
use App\Models\MasterLookup;
use App\Models\PegawaiKeluar;
use App\Models\PegawaiKeluarAssetClearance;
use App\Models\settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PegawaiKeluarTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $employee;
    private User $otherEmployee;
    private User $hrd;
    private User $itReceiver;
    private Jabatan $jabatan;
    private Lokasi $lokasi;

    protected function setUp(): void
    {
        parent::setUp();

        settings::create([
            'name' => 'Absensi',
            'logo' => 'logo/absensi.png',
        ]);

        $this->jabatan = Jabatan::create([
            'nama_jabatan' => 'Administrasi',
        ]);

        $this->lokasi = Lokasi::create([
            'nama_lokasi' => 'Gudang IT',
        ]);

        $this->admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.test',
            'username' => 'admin',
            'is_admin' => 'admin',
            'jabatan_id' => $this->jabatan->id,
        ]);

        $this->employee = User::create([
            'name' => 'Employee One',
            'email' => 'employee@example.test',
            'username' => 'employee',
            'is_admin' => 'user',
            'jabatan_id' => $this->jabatan->id,
        ]);

        $this->otherEmployee = User::create([
            'name' => 'Employee Two',
            'email' => 'other@example.test',
            'username' => 'other',
            'is_admin' => 'user',
            'jabatan_id' => $this->jabatan->id,
        ]);

        $this->hrd = User::create([
            'name' => 'HRD One',
            'email' => 'hrd@example.test',
            'username' => 'hrd',
            'is_admin' => 'user',
            'jabatan_id' => $this->jabatan->id,
        ]);

        $this->itReceiver = User::create([
            'name' => 'IT Receiver',
            'email' => 'it.receiver@example.test',
            'username' => 'itreceiver',
            'is_admin' => 'user',
            'jabatan_id' => $this->jabatan->id,
        ]);
    }

    /** @test */
    public function exit_type_dropdown_uses_default_options_when_master_lookup_is_empty()
    {
        $this->assertSame(0, MasterLookup::count());

        $response = $this->actingAs($this->admin)->get('/exit/tambah');

        $response->assertOk();
        $response->assertSee('PHK');
        $response->assertSee('Mengundurkan Diri');
        $response->assertSee('Meninggal Dunia');
        $response->assertSee('Pensiun');
    }

    /** @test */
    public function admin_can_store_exit_request_with_default_exit_type()
    {
        $response = $this->actingAs($this->admin)->post('/exit/store', [
            'user_id' => $this->employee->id,
            'jenis' => 'Mengundurkan Diri',
            'alasan' => 'Kontrak selesai.',
            'tanggal' => '2026-05-31',
        ]);

        $response->assertRedirect('/exit');
        $this->assertDatabaseHas('pegawai_keluars', [
            'user_id' => $this->employee->id,
            'jenis' => 'Mengundurkan Diri',
            'status' => 'PENDING',
        ]);
    }

    /** @test */
    public function regular_user_cannot_submit_exit_request_for_another_employee()
    {
        $response = $this->actingAs($this->employee)->post('/exit/store', [
            'user_id' => $this->otherEmployee->id,
            'jenis' => 'PHK',
            'alasan' => 'Percobaan ubah user id.',
            'tanggal' => '2026-05-31',
        ]);

        $response->assertRedirect('/exit');
        $this->assertDatabaseHas('pegawai_keluars', [
            'user_id' => $this->employee->id,
            'jenis' => 'PHK',
        ]);
        $this->assertDatabaseMissing('pegawai_keluars', [
            'user_id' => $this->otherEmployee->id,
            'jenis' => 'PHK',
        ]);
    }

    /** @test */
    public function approval_uses_authenticated_user_as_approver()
    {
        $pegawaiKeluar = PegawaiKeluar::create([
            'user_id' => $this->employee->id,
            'jenis' => 'Pensiun',
            'alasan' => 'Masa kerja selesai.',
            'tanggal' => '2026-05-31',
            'status' => 'PENDING',
        ]);

        $response = $this->actingAs($this->admin)->post('/exit/approval/' . $pegawaiKeluar->id, [
            'status' => 'APPROVED',
            'notes' => 'Disetujui.',
            'approved_by' => $this->otherEmployee->id,
        ]);

        $response->assertRedirect('/exit');
        $this->assertDatabaseHas('pegawai_keluars', [
            'id' => $pegawaiKeluar->id,
            'status' => 'APPROVED',
            'approved_by' => $this->admin->id,
            'tanggal_approval' => now()->toDateString(),
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $this->employee->id,
            'masa_berlaku' => '2026-05-31',
        ]);
    }

    /** @test */
    public function approval_is_blocked_while_employee_asset_is_not_clear()
    {
        $inventory = $this->createInventory(['stok' => 0]);
        $transaction = $this->createHeldAssetTransaction($inventory);
        $pegawaiKeluar = $this->createExitRequest();

        $response = $this->actingAs($this->admin)->post('/exit/approval/' . $pegawaiKeluar->id, [
            'status' => 'APPROVED',
            'notes' => 'Disetujui.',
        ]);

        $response->assertRedirect('/exit/' . $pegawaiKeluar->id . '/assets');
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('pegawai_keluar_asset_clearances', [
            'pegawai_keluar_id' => $pegawaiKeluar->id,
            'inventory_stock_transaction_id' => $transaction->id,
            'status' => PegawaiKeluarAssetClearance::STATUS_PENDING,
        ]);
        $this->actingAs($this->admin)
            ->get('/exit/' . $pegawaiKeluar->id . '/assets')
            ->assertOk()
            ->assertSee('Laptop Kantor')
            ->assertSee('Belum Kembali');
        $this->assertDatabaseHas('pegawai_keluars', [
            'id' => $pegawaiKeluar->id,
            'status' => 'PENDING',
        ]);
        $this->assertNull($this->employee->fresh()->masa_berlaku);
    }

    /** @test */
    public function admin_can_return_employee_asset_and_then_approve_exit()
    {
        Storage::fake('public');

        $inventory = $this->createInventory([
            'stok' => 0,
            'kondisi' => 'Baik',
            'status_barang' => 'Dipakai',
        ]);
        $transaction = $this->createHeldAssetTransaction($inventory);
        $pegawaiKeluar = $this->createExitRequest();

        $response = $this->actingAs($this->admin)->post('/exit/' . $pegawaiKeluar->id . '/assets/' . $transaction->id . '/return', [
            'tanggal_kembali' => '2026-05-31',
            'kondisi_barang' => 'Rusak ringan',
            'kelengkapan' => 'Laptop dan charger lengkap',
            'status_barang' => 'Perlu Perbaikan',
            'lokasi_id' => $this->lokasi->id,
            'it_receiver_user_id' => $this->itReceiver->id,
            'known_by_user_id' => $this->hrd->id,
            'catatan' => 'Engsel perlu dicek.',
        ]);

        $response->assertRedirect('/exit/' . $pegawaiKeluar->id . '/assets');
        $inventory->refresh();
        $this->assertSame(1.0, (float) $inventory->stok);
        $this->assertSame('Rusak ringan', $inventory->kondisi);
        $this->assertSame('Perlu Perbaikan', $inventory->status_barang);

        $returnTransaction = InventoryStockTransaction::where('return_for_transaction_id', $transaction->id)->first();
        $this->assertNotNull($returnTransaction);
        $this->assertSame('masuk', $returnTransaction->jenis_transaksi);
        $this->assertSame($pegawaiKeluar->id, (int) $returnTransaction->pegawai_keluar_id);
        $this->assertStringContainsString('Pengembalian dari ' . $this->employee->name, $returnTransaction->sumber_barang);

        $document = InventoryReturnDocument::first();
        $this->assertNotNull($document);
        $this->assertSame('001 / IT-BAST-PB / V / 2026', $document->nomor_surat);
        $this->assertSame($this->employee->id, (int) $document->employee_user_id);
        $this->assertSame($this->itReceiver->id, (int) $document->it_receiver_user_id);
        $this->assertSame($this->hrd->id, (int) $document->known_by_user_id);
        Storage::disk('public')->assertExists($document->file_pdf);

        $this->actingAs($this->admin)
            ->get('/exit/' . $pegawaiKeluar->id . '/assets')
            ->assertOk()
            ->assertSee('Dikembalikan')
            ->assertSee($document->nomor_surat);

        $this->actingAs($this->employee)
            ->get('/my-inventory-return-bast')
            ->assertOk()
            ->assertSee($document->nomor_surat);

        $this->actingAs($this->employee)
            ->get('/my-inventory-return-bast/' . $document->id)
            ->assertOk()
            ->assertSee('Laptop Kantor')
            ->assertSee('Rusak ringan');

        $this->assertDatabaseHas('pegawai_keluar_asset_clearances', [
            'pegawai_keluar_id' => $pegawaiKeluar->id,
            'inventory_stock_transaction_id' => $transaction->id,
            'status' => PegawaiKeluarAssetClearance::STATUS_RETURNED,
            'returned_inventory_stock_transaction_id' => $returnTransaction->id,
        ]);

        $approval = $this->actingAs($this->admin)->post('/exit/approval/' . $pegawaiKeluar->id, [
            'status' => 'APPROVED',
            'notes' => 'Aset sudah kembali.',
        ]);

        $approval->assertRedirect('/exit');
        $this->assertDatabaseHas('pegawai_keluars', [
            'id' => $pegawaiKeluar->id,
            'status' => 'APPROVED',
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $this->employee->id,
            'masa_berlaku' => '2026-05-31',
        ]);
    }

    /** @test */
    public function admin_can_waive_asset_clearance_with_required_reason()
    {
        $inventory = $this->createInventory(['stok' => 0]);
        $transaction = $this->createHeldAssetTransaction($inventory);
        $pegawaiKeluar = $this->createExitRequest();

        $this->actingAs($this->employee)
            ->post('/exit/' . $pegawaiKeluar->id . '/assets/' . $transaction->id . '/waive', [
                'waiver_reason' => 'Aset hilang dan diproses manual.',
            ])
            ->assertRedirect('/absen');

        $this->actingAs($this->admin)
            ->post('/exit/' . $pegawaiKeluar->id . '/assets/' . $transaction->id . '/waive', [
                'waiver_reason' => '',
            ])
            ->assertSessionHasErrors('waiver_reason');

        $this->actingAs($this->admin)
            ->post('/exit/' . $pegawaiKeluar->id . '/assets/' . $transaction->id . '/waive', [
                'waiver_reason' => 'Aset sedang diproses penggantian biaya.',
            ])
            ->assertRedirect('/exit/' . $pegawaiKeluar->id . '/assets');

        $this->assertDatabaseHas('pegawai_keluar_asset_clearances', [
            'pegawai_keluar_id' => $pegawaiKeluar->id,
            'inventory_stock_transaction_id' => $transaction->id,
            'status' => PegawaiKeluarAssetClearance::STATUS_WAIVED,
            'waived_by_user_id' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post('/exit/approval/' . $pegawaiKeluar->id, [
                'status' => 'APPROVED',
                'notes' => 'Clearance dikecualikan.',
            ])
            ->assertRedirect('/exit');
    }

    /** @test */
    public function assigned_return_document_signers_can_only_sign_their_roles()
    {
        Storage::fake('public');

        $inventory = $this->createInventory(['stok' => 0]);
        $transaction = $this->createHeldAssetTransaction($inventory);
        $pegawaiKeluar = $this->createExitRequest();

        $this->actingAs($this->admin)->post('/exit/' . $pegawaiKeluar->id . '/assets/' . $transaction->id . '/return', [
            'tanggal_kembali' => '2026-05-31',
            'kondisi_barang' => 'Baik',
            'kelengkapan' => 'Lengkap',
            'status_barang' => 'Tersedia',
            'lokasi_id' => $this->lokasi->id,
            'it_receiver_user_id' => $this->itReceiver->id,
            'known_by_user_id' => $this->hrd->id,
        ]);

        $document = InventoryReturnDocument::first();
        $signature = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/p9sAAAAASUVORK5CYII=';

        $this->actingAs($this->otherEmployee)
            ->post('/my-inventory-return-bast/' . $document->id . '/sign/employee', [
                'agreement' => '1',
                'signature_data' => $signature,
            ])
            ->assertNotFound();

        $this->actingAs($this->employee)
            ->post('/my-inventory-return-bast/' . $document->id . '/sign/employee', [
                'agreement' => '1',
                'signature_data' => $signature,
            ])
            ->assertRedirect('/my-inventory-return-bast/' . $document->id);

        $this->actingAs($this->employee)
            ->post('/my-inventory-return-bast/' . $document->id . '/sign/it_receiver', [
                'agreement' => '1',
                'signature_data' => $signature,
            ])
            ->assertNotFound();

        $document->refresh();
        $this->assertNotNull($document->employee_signed_at);
        $this->assertNull($document->it_receiver_signed_at);
        Storage::disk('public')->assertExists($document->employee_signature_image);
    }

    private function createExitRequest(array $overrides = []): PegawaiKeluar
    {
        return PegawaiKeluar::create(array_merge([
            'user_id' => $this->employee->id,
            'jenis' => 'Mengundurkan Diri',
            'alasan' => 'Kontrak selesai.',
            'tanggal' => '2026-05-31',
            'status' => 'PENDING',
        ], $overrides));
    }

    private function createInventory(array $overrides = []): Inventory
    {
        return Inventory::create(array_merge([
            'kode_barang' => 'INV/TEST001',
            'nama_barang' => 'Laptop Kantor',
            'jenis_barang' => 'Laptop',
            'merk_tipe' => 'ThinkPad',
            'serial_number' => 'SN-001',
            'stok' => 1,
            'uom' => 'Unit',
            'kondisi' => 'Baik',
            'status_barang' => 'Aktif',
            'lokasi_id' => $this->lokasi->id,
            'jabatan_id' => $this->jabatan->id,
        ], $overrides));
    }

    private function createHeldAssetTransaction(Inventory $inventory): InventoryStockTransaction
    {
        return InventoryStockTransaction::create([
            'inventory_id' => $inventory->id,
            'jenis_transaksi' => 'keluar',
            'jumlah' => 1,
            'stok_sebelum' => 1,
            'stok_sesudah' => 0,
            'tanggal_transaksi' => '2026-05-30',
            'penerima_user_id' => $this->employee->id,
            'penerima_barang' => $this->employee->name,
            'jabatan_penerima' => $this->jabatan->nama_jabatan,
            'departemen_penerima' => $this->jabatan->nama_jabatan,
            'keperluan' => 'Fasilitas kerja',
            'kondisi_barang' => 'Baik',
            'diproses_oleh' => $this->admin->id,
        ]);
    }
}
