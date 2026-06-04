<?php

namespace Tests\Feature;

use App\Models\MappingShift;
use App\Models\Shift;
use App\Models\User;
use App\Services\AttendanceRecapService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceRecapServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_counts_absence_and_excludes_libur_from_attendance_percentage(): void
    {
        $user = User::create([
            'name' => 'MGS ILHAM ZUHDI',
            'username' => 'mgs',
            'email' => 'mgs@example.test',
            'is_admin' => 'user',
        ]);
        $shift = Shift::create([
            'nama_shift' => 'Pagi',
            'jam_masuk' => '08:00:00',
            'jam_keluar' => '17:00:00',
        ]);

        foreach ([
            ['2026-05-01', 'Masuk'],
            ['2026-05-02', 'Tidak Masuk'],
            ['2026-05-03', 'Libur'],
            ['2026-05-04', 'Izin Telat'],
        ] as [$tanggal, $status]) {
            MappingShift::create([
                'user_id' => $user->id,
                'shift_id' => $shift->id,
                'tanggal' => $tanggal,
                'status_absen' => $status,
                'jam_absen' => $status === 'Libur' ? null : '08:00',
                'telat' => $status === 'Izin Telat' ? 300 : 0,
                'pulang_cepat' => 0,
            ]);
        }

        $summary = app(AttendanceRecapService::class)
            ->summaryForUser($user->id, '2026-05-01', '2026-05-04');

        $this->assertSame(2, $summary['total_hadir']);
        $this->assertSame(1, $summary['total_alfa']);
        $this->assertSame(1, $summary['libur']);
        $this->assertSame(3, $summary['hari_kerja']);
        $this->assertSame(1, $summary['jumlah_telat']);
        $this->assertEqualsWithDelta(66.67, $summary['persentase_kehadiran'], 0.01);
    }
}
