<?php

namespace Tests\Feature;

use App\Models\MappingShift;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmartAbsenImportControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function import_update_does_not_clear_existing_clock_times_when_uploaded_row_is_empty()
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@example.test',
            'is_admin' => 'admin',
        ]);

        $employee = User::create([
            'name' => 'Employee',
            'username' => 'employee',
            'email' => 'employee@example.test',
            'is_admin' => 'user',
        ]);

        $shift = Shift::create([
            'nama_shift' => 'Pagi',
            'jam_masuk' => '08:00:00',
            'jam_keluar' => '17:00:00',
        ]);

        $existing = MappingShift::create([
            'user_id' => $employee->id,
            'shift_id' => $shift->id,
            'tanggal' => '2026-05-29',
            'jam_absen' => '08:05',
            'jam_pulang' => '17:10',
            'telat' => 300,
            'pulang_cepat' => 0,
            'status_absen' => 'Hadir',
            'keterangan_masuk' => 'QR Absen',
            'keterangan_pulang' => 'QR Pulang',
        ]);

        $response = $this->actingAs($admin)->postJson('/smart-import-absen/import', [
            'import_rows' => [[
                'user_id' => $employee->id,
                'shift_id' => $shift->id,
                'tanggal' => '2026-05-29',
                'jam_absen' => null,
                'jam_pulang' => null,
                'status_absen' => 'Hadir',
                'telat' => 0,
                'pulang_cepat' => 0,
            ]],
        ]);

        $response->assertOk()
            ->assertJsonPath('stats.updated', 1);

        $existing->refresh();
        $this->assertSame('08:05', $existing->jam_absen);
        $this->assertSame('17:10', $existing->jam_pulang);
        $this->assertSame('300', $existing->telat);
        $this->assertSame('0', $existing->pulang_cepat);
        $this->assertSame('QR Absen', $existing->keterangan_masuk);
        $this->assertSame('QR Pulang', $existing->keterangan_pulang);
    }
}
