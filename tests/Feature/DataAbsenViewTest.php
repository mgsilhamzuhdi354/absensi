<?php

namespace Tests\Feature;

use App\Models\MappingShift;
use App\Models\Jabatan;
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
}
