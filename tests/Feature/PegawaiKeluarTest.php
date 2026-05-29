<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\MasterLookup;
use App\Models\PegawaiKeluar;
use App\Models\settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PegawaiKeluarTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $employee;
    private User $otherEmployee;

    protected function setUp(): void
    {
        parent::setUp();

        settings::create([
            'name' => 'Absensi',
            'logo' => 'logo/absensi.png',
        ]);

        $jabatan = Jabatan::create([
            'nama_jabatan' => 'Administrasi',
        ]);

        $this->admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.test',
            'username' => 'admin',
            'is_admin' => 'admin',
            'jabatan_id' => $jabatan->id,
        ]);

        $this->employee = User::create([
            'name' => 'Employee One',
            'email' => 'employee@example.test',
            'username' => 'employee',
            'is_admin' => 'user',
            'jabatan_id' => $jabatan->id,
        ]);

        $this->otherEmployee = User::create([
            'name' => 'Employee Two',
            'email' => 'other@example.test',
            'username' => 'other',
            'is_admin' => 'user',
            'jabatan_id' => $jabatan->id,
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
}
