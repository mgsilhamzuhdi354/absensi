<?php

namespace Tests\Feature;

use App\Models\MappingShift;
use App\Models\Jabatan;
use App\Models\PegawaiKeluar;
use App\Models\Shift;
use App\Models\settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DataAbsenViewTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function data_absen_does_not_display_fake_masuk_rekap_label(): void
    {
        settings::create([
            'name' => 'Absensi',
            'logo' => 'logo/absensi.png',
        ]);

        $jabatan = Jabatan::create([
            'nama_jabatan' => 'Staff',
        ]);

        $shift = Shift::create([
            'nama_shift' => 'Office',
            'jam_masuk' => '08:00:00',
            'jam_keluar' => '17:00:00',
        ]);

        $admin = User::create([
            'name' => 'Admin Rekap',
            'username' => 'adminrekap',
            'email' => 'adminrekap@example.test',
            'is_admin' => 'admin',
            'jabatan_id' => $jabatan->id,
        ]);

        $employee = User::create([
            'name' => 'Employee Alfa',
            'username' => 'employeealfa',
            'email' => 'employeealfa@example.test',
            'is_admin' => 'user',
            'jabatan_id' => $jabatan->id,
        ]);

        $today = now()->toDateString();

        MappingShift::create([
            'user_id' => $admin->id,
            'shift_id' => $shift->id,
            'tanggal' => $today,
            'jam_absen' => null,
            'jam_pulang' => null,
            'status_absen' => 'Masuk',
            'keterangan_masuk' => 'Smart Import Absensi (Rekap Mesin)',
        ]);

        MappingShift::create([
            'user_id' => $employee->id,
            'shift_id' => $shift->id,
            'tanggal' => $today,
            'jam_absen' => null,
            'jam_pulang' => null,
            'status_absen' => 'Tidak Masuk',
            'keterangan_masuk' => 'Smart Import Absensi (Rekap Mesin)',
        ]);

        $response = $this->actingAs($admin)->get('/data-absen');

        $response->assertOk();
        $response->assertDontSee('Masuk (Rekap)', false);
        $response->assertSee('Tidak Masuk', false);
    }

    /** @test */
    public function data_absen_defaults_to_active_employees_and_has_exit_filter(): void
    {
        settings::create([
            'name' => 'Absensi',
            'logo' => 'logo/absensi.png',
        ]);

        $jabatan = Jabatan::create([
            'nama_jabatan' => 'Staff',
        ]);

        $shift = Shift::create([
            'nama_shift' => 'Office',
            'jam_masuk' => '08:00:00',
            'jam_keluar' => '17:00:00',
        ]);

        $admin = User::create([
            'name' => 'Admin Filter',
            'username' => 'adminfilter',
            'email' => 'adminfilter@example.test',
            'is_admin' => 'admin',
            'jabatan_id' => $jabatan->id,
        ]);

        $activeEmployee = User::create([
            'name' => 'Pegawai Aktif Filter',
            'username' => 'aktif-filter',
            'email' => 'aktif-filter@example.test',
            'is_admin' => 'user',
            'jabatan_id' => $jabatan->id,
        ]);

        $exitEmployee = User::create([
            'name' => 'Pegawai Keluar Filter',
            'username' => 'keluar-filter',
            'email' => 'keluar-filter@example.test',
            'is_admin' => 'user',
            'jabatan_id' => $jabatan->id,
        ]);

        PegawaiKeluar::create([
            'user_id' => $exitEmployee->id,
            'tanggal' => now()->toDateString(),
            'jenis' => 'PHK',
            'status' => PegawaiKeluar::STATUS_APPROVED,
        ]);

        MappingShift::create([
            'user_id' => $activeEmployee->id,
            'shift_id' => $shift->id,
            'tanggal' => now()->toDateString(),
            'jam_absen' => '08:00',
            'status_absen' => 'Masuk',
        ]);

        MappingShift::create([
            'user_id' => $exitEmployee->id,
            'shift_id' => $shift->id,
            'tanggal' => now()->toDateString(),
            'jam_absen' => '08:05',
            'status_absen' => 'Masuk',
        ]);

        $this->actingAs($admin)
            ->get('/data-absen')
            ->assertOk()
            ->assertSee('Pegawai Aktif Filter')
            ->assertDontSee('Pegawai Keluar Filter');

        $this->actingAs($admin)
            ->get('/data-absen?pegawai_status=keluar')
            ->assertOk()
            ->assertSee('Pegawai Keluar Filter')
            ->assertDontSee('Pegawai Aktif Filter');
    }

    /** @test */
    public function data_absen_hides_archived_duplicate_rows(): void
    {
        settings::create([
            'name' => 'Absensi',
            'logo' => 'logo/absensi.png',
        ]);

        $jabatan = Jabatan::create([
            'nama_jabatan' => 'Staff',
        ]);

        $shift = Shift::create([
            'nama_shift' => 'Office',
            'jam_masuk' => '08:00:00',
            'jam_keluar' => '17:00:00',
        ]);

        $admin = User::create([
            'name' => 'Admin Duplicate View',
            'username' => 'adminduplicateview',
            'email' => 'adminduplicateview@example.test',
            'is_admin' => 'admin',
            'jabatan_id' => $jabatan->id,
        ]);

        $employee = User::create([
            'name' => 'Pegawai Duplicate View',
            'username' => 'duplicate-view',
            'email' => 'duplicate-view@example.test',
            'is_admin' => 'user',
            'jabatan_id' => $jabatan->id,
        ]);

        $canonical = MappingShift::create([
            'user_id' => $employee->id,
            'shift_id' => $shift->id,
            'tanggal' => now()->toDateString(),
            'jam_absen' => '08:00',
            'status_absen' => 'Masuk',
        ]);

        MappingShift::create([
            'user_id' => $employee->id,
            'shift_id' => $shift->id,
            'tanggal' => now()->toDateString(),
            'status_absen' => 'Archived Marker',
            'merged_into_id' => $canonical->id,
            'merged_at' => now(),
            'merge_note' => 'arsip test',
        ]);

        $this->actingAs($admin)
            ->get('/data-absen')
            ->assertOk()
            ->assertSee('Pegawai Duplicate View')
            ->assertDontSee('Archived Marker');
    }
}
