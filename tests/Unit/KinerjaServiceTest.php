<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\KinerjaService;
use App\Models\User;
use App\Models\MappingShift;
use App\Models\LaporanKinerja;
use App\Models\JenisKinerja;
use Illuminate\Foundation\Testing\RefreshDatabase;

class KinerjaServiceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function can_reset_attendance_points()
    {
        // Create test data
        $user = User::factory()->create();
        $mappingShift = MappingShift::factory()->create(['user_id' => $user->id]);

        LaporanKinerja::create([
            'user_id' => $user->id,
            'reference' => 'App\Models\MappingShift',
            'reference_id' => $mappingShift->id,
            'jenis_kinerja_id' => 1,
            'nilai' => 10,  // Use 'nilai' not 'bobot'
            'tanggal' => date('Y-m-d'),
        ]);

        $this->assertDatabaseHas('laporan_kinerjas', [
            'reference_id' => $mappingShift->id,
        ]);

        // Delete points
        KinerjaService::deleteAttendancePoints($mappingShift->id, $user->id);

        // Verify reset, not deleted, so historical charts can still show zero points.
        $this->assertDatabaseHas('laporan_kinerjas', [
            'reference_id' => $mappingShift->id,
            'nilai' => 0,
            'keterangan' => 'Shift dihapus oleh admin - Poin direset ke 0',
        ]);
    }

    /** @test */
    public function update_attendance_points_creates_zero_point_for_incomplete_attendance()
    {
        $user = User::factory()->create();
        $mappingShift = MappingShift::factory()->create([
            'user_id' => $user->id,
            'jam_absen' => null,
            'jam_pulang' => null,
            'status_absen' => 'Tidak Masuk',
            'tanggal' => '2026-06-22',
        ]);

        KinerjaService::updateAttendancePoints($mappingShift->id, $user->id);

        $jenisIncomplete = JenisKinerja::where('nama', 'Absensi Tidak Lengkap')->firstOrFail();

        $this->assertDatabaseHas('laporan_kinerjas', [
            'user_id' => $user->id,
            'reference' => 'App\Models\MappingShift',
            'reference_id' => $mappingShift->id,
            'jenis_kinerja_id' => $jenisIncomplete->id,
            'nilai' => 0,
        ]);
    }

    /** @test */
    public function distance_calculation_is_accurate()
    {
        // Jakarta coordinates
        $lat1 = -6.200000;
        $lon1 = 106.816666;

        // Nearby point (approximately 1km)
        $lat2 = -6.210000;
        $lon2 = 106.816666;

        // Using the distance method from AbsenController
        $controller = new \App\Http\Controllers\AbsenController();
        $distance = $controller->distance($lat1, $lon1, $lat2, $lon2, "K") * 1000; // in meters

        // Distance should be approximately 1100 meters
        $this->assertGreaterThan(1000, $distance);
        $this->assertLessThan(1200, $distance);
    }
}
