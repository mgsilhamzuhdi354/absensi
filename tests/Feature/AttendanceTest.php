<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\MappingShift;
use App\Models\Shift;
use App\Models\Lokasi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed basic data
        $this->setUpTestData();
    }

    private function setUpTestData()
    {
        // Create location
        $lokasi = Lokasi::create([
            'nama_lokasi' => 'Test Office',
            'lat_kantor' => '-6.200000',
            'long_kantor' => '106.816666',
            'radius' => 100,
        ]);

        // Create shift
        $shift = Shift::create([
            'nama_shift' => 'Pagi',
            'jam_masuk' => '08:00:00',
            'jam_keluar' => '17:00:00',
        ]);

        // Create user
        $user = User::create([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'lokasi_id' => $lokasi->id,
        ]);

        // Create mapping shift for today
        MappingShift::create([
            'user_id' => $user->id,
            'tanggal' => date('Y-m-d'),
            'shift_id' => $shift->id,
            'lock_location' => 0,
        ]);
    }

    /** @test */
    public function user_can_clock_in_when_shift_exists()
    {
        $user = User::first();
        $mappingShift = MappingShift::where('user_id', $user->id)
            ->where('tanggal', date('Y-m-d'))
            ->first();

        $response = $this->actingAs($user)
            ->put("/absen/masuk/{$mappingShift->id}", [
                'jam_absen' => date('H:i:s'),
                'foto_jam_absen' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
                'lat_absen' => '-6.200000',
                'long_absen' => '106.816666',
                'telat' => 0,
                'jarak_masuk' => 50,
                'status_absen' => 'Masuk',
            ]);

        $response->assertStatus(302); // Redirect after success

        $this->assertDatabaseHas('mapping_shifts', [
            'id' => $mappingShift->id,
            'status_absen' => 'Masuk',
        ]);
    }

    /** @test */
    public function attendance_fails_gracefully_when_shift_is_null()
    {
        $user = User::first();
        $mappingShift = MappingShift::where('user_id', $user->id)->first();

        // Delete shift relation to simulate null
        $mappingShift->shift_id = 999; // Non-existent shift
        $mappingShift->save();

        $response = $this->actingAs($user)
            ->put("/absen/masuk/{$mappingShift->id}", [
                'jam_absen' => date('H:i:s'),
                'foto_jam_absen' => 'data:image/png;base64,test',
                'lat_absen' => '-6.200000',
                'long_absen' => '106.816666',
            ]);

        // Should return error, not crash
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /** @test */
    public function attendance_works_when_lokasi_is_null_and_lock_location_is_off()
    {
        $user = User::first();
        $user->lokasi_id = null;
        $user->save();

        $mappingShift = MappingShift::where('user_id', $user->id)->first();
        $mappingShift->lock_location = 0;
        $mappingShift->save();

        $response = $this->actingAs($user)
            ->put("/absen/masuk/{$mappingShift->id}", [
                'jam_absen' => date('H:i:s'),
                'foto_jam_absen' => 'data:image/png;base64,test',
                'lat_absen' => '-6.200000',
                'long_absen' => '106.816666',
                'telat' => 0,
                'jarak_masuk' => 0,
                'status_absen' => 'Masuk',
            ]);

        // Should succeed even without location
        $response->assertStatus(302);
    }

    /** @test */
    public function attendance_fails_when_lokasi_is_null_and_lock_location_is_on()
    {
        $user = User::first();
        $user->lokasi_id = null;
        $user->save();

        $mappingShift = MappingShift::where('user_id', $user->id)->first();
        $mappingShift->lock_location = 1;
        $mappingShift->save();

        $response = $this->actingAs($user)
            ->put("/absen/masuk/{$mappingShift->id}", [
                'jam_absen' => date('H:i:s'),
                'foto_jam_absen' => 'data:image/png;base64,test',
                'lat_absen' => '-6.200000',
                'long_absen' => '106.816666',
            ]);

        // Should return error message
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    /** @test */
    public function qr_attendance_works_without_authentication()
    {
        $mappingShift = MappingShift::first();

        $response = $this->postJson('/qr-masuk', [
            'user_id' => $mappingShift->user_id,
            'tanggal' => $mappingShift->tanggal,
            'foto_jam_absen' => 'data:image/png;base64,test',
            'lat' => '-6.200000',
            'long' => '106.816666',
        ]);

        // Should work without login
        $response->assertJsonFragment(['status' => 'success']);
    }

    /** @test */
    public function deleting_mapping_shift_also_deletes_performance_points()
    {
        $mappingShift = MappingShift::first();
        $shiftId = $mappingShift->id;
        $userId = $mappingShift->user_id;

        // Create performance point with correct column names
        DB::table('laporan_kinerjas')->insert([
            'user_id' => $userId,
            'reference_id' => $shiftId,
            'jenis_kinerja_id' => 1,
            'nilai' => 10,  // Use 'nilai' not 'bobot'
            'tanggal' => date('Y-m-d'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('laporan_kinerjas', [
            'reference_id' => $shiftId,
            'user_id' => $userId,
        ]);

        // Delete shift through service (cascade)
        \App\Services\KinerjaService::deleteAttendancePoints($shiftId, $userId);

        // Performance point should be deleted
        $this->assertDatabaseMissing('laporan_kinerjas', [
            'reference_id' => $shiftId,
        ]);
    }

    /** @test */
    public function invalid_image_format_is_handled_gracefully()
    {
        $user = User::first();
        $mappingShift = MappingShift::where('user_id', $user->id)->first();

        $response = $this->actingAs($user)
            ->put("/absen/masuk/{$mappingShift->id}", [
                'jam_absen' => date('H:i:s'),
                'foto_jam_absen' => 'not-a-valid-base64-image',
                'lat_absen' => '-6.200000',
                'long_absen' => '106.816666',
            ]);

        // Should not crash - either redirect or show error
        $this->assertTrue(
            $response->status() === 302 || $response->status() === 200,
            "Expected 302 or 200, got {$response->status()}"
        );
    }
}
