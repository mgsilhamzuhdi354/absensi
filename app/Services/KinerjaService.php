<?php

namespace App\Services;

use App\Models\JenisKinerja;
use App\Models\LaporanKinerja;
use App\Models\MappingShift;
use App\Models\Penugasan;
use App\Models\Rapat;

class KinerjaService
{
    /**
     * Update attendance points when attendance data is edited
     * This will update (or create) the performance point based on new telat/pulang_cepat status
     */
    public static function updateAttendancePoints($mappingShiftId, $userId)
    {
        date_default_timezone_set('Asia/Jakarta');

        $mappingShift = MappingShift::find($mappingShiftId);
        if (!$mappingShift) {
            return;
        }

        // Handle clock-in points (Ontime vs Telat)
        if ($mappingShift->jam_absen) {
            self::updateClockInPoints($mappingShift, $userId);
        }

        // Handle clock-out points (Tepat Waktu vs Pulang Cepat)
        if ($mappingShift->jam_pulang) {
            self::updateClockOutPoints($mappingShift, $userId);
        }

        // Recalculate running totals
        self::recalculateUserPoints($userId);
    }

    /**
     * Update clock-in related points
     */
    private static function updateClockInPoints($mappingShift, $userId)
    {
        // Get jenis kinerja for clock-in
        $jenisOntime = JenisKinerja::where('nama', 'Presensi Kehadiran Ontime')->first();
        $jenisTelat = JenisKinerja::where('nama', 'Telat Presensi Masuk')->first();

        if (!$jenisOntime || !$jenisTelat) {
            return;
        }

        // Find existing clock-in point for this shift
        $existingPoint = LaporanKinerja::where('reference', 'App\Models\MappingShift')
            ->where('reference_id', $mappingShift->id)
            ->whereIn('jenis_kinerja_id', [$jenisOntime->id, $jenisTelat->id])
            ->first();

        // Determine correct jenis based on current telat status
        $correctJenis = ($mappingShift->telat > 0) ? $jenisTelat : $jenisOntime;

        if ($existingPoint) {
            // Update existing point if jenis changed
            if ($existingPoint->jenis_kinerja_id != $correctJenis->id) {
                $existingPoint->update([
                    'jenis_kinerja_id' => $correctJenis->id,
                    'nilai' => $correctJenis->bobot,
                ]);
            }
        } else {
            // Create new point if doesn't exist
            LaporanKinerja::create([
                'user_id' => $userId,
                'tanggal' => $mappingShift->tanggal,
                'jenis_kinerja_id' => $correctJenis->id,
                'nilai' => $correctJenis->bobot,
                'penilaian_berjalan' => 0, // Will be recalculated
                'reference' => 'App\Models\MappingShift',
                'reference_id' => $mappingShift->id,
            ]);
        }
    }

    /**
     * Update clock-out related points
     */
    private static function updateClockOutPoints($mappingShift, $userId)
    {
        // Get jenis kinerja for clock-out
        $jenisTepat = JenisKinerja::where('nama', 'Pulang tepat waktu')->first();
        $jenisCepat = JenisKinerja::where('nama', 'Pulang Sebelum waktunya')->first();

        if (!$jenisTepat || !$jenisCepat) {
            return;
        }

        // Find existing clock-out point for this shift
        $existingPoint = LaporanKinerja::where('reference', 'App\Models\MappingShift')
            ->where('reference_id', $mappingShift->id)
            ->whereIn('jenis_kinerja_id', [$jenisTepat->id, $jenisCepat->id])
            ->first();

        // Determine correct jenis based on current pulang_cepat status
        $correctJenis = ($mappingShift->pulang_cepat > 0) ? $jenisCepat : $jenisTepat;

        if ($existingPoint) {
            // Update existing point if jenis changed
            if ($existingPoint->jenis_kinerja_id != $correctJenis->id) {
                $existingPoint->update([
                    'jenis_kinerja_id' => $correctJenis->id,
                    'nilai' => $correctJenis->bobot,
                ]);
            }
        } else {
            // Create new point if doesn't exist
            LaporanKinerja::create([
                'user_id' => $userId,
                'tanggal' => $mappingShift->tanggal,
                'jenis_kinerja_id' => $correctJenis->id,
                'nilai' => $correctJenis->bobot,
                'penilaian_berjalan' => 0, // Will be recalculated
                'reference' => 'App\Models\MappingShift',
                'reference_id' => $mappingShift->id,
            ]);
        }
    }

    /**
     * Delete attendance points when shift is deleted
     */
    public static function deleteAttendancePoints($mappingShiftId, $userId)
    {
        // Delete all points associated with this shift
        LaporanKinerja::where('reference', 'App\Models\MappingShift')
            ->where('reference_id', $mappingShiftId)
            ->delete();

        // Recalculate running totals
        self::recalculateUserPoints($userId);
    }

    /**
     * Delete penugasan points when penugasan is deleted
     */
    public static function deletePenugasanPoints($penugasanId, $userId)
    {
        LaporanKinerja::where('reference', 'App\Models\Penugasan')
            ->where('reference_id', $penugasanId)
            ->delete();

        self::recalculateUserPoints($userId);
    }

    /**
     * Delete rapat points when rapat attendance is removed
     */
    public static function deleteRapatPoints($rapatId, $userId)
    {
        LaporanKinerja::where('reference', 'App\Models\Rapat')
            ->where('reference_id', $rapatId)
            ->where('user_id', $userId)
            ->delete();

        self::recalculateUserPoints($userId);
    }

    /**
     * Recalculate all penilaian_berjalan values for a user
     * This ensures consistency after edits or deletions
     */
    public static function recalculateUserPoints($userId)
    {
        // Get all points for user ordered by date and ID
        $points = LaporanKinerja::where('user_id', $userId)
            ->orderBy('tanggal', 'ASC')
            ->orderBy('id', 'ASC')
            ->get();

        $runningTotal = 0;
        foreach ($points as $point) {
            $runningTotal += $point->nilai;
            $point->update(['penilaian_berjalan' => $runningTotal]);
        }
    }

    /**
     * Update penugasan status and manage points accordingly
     * Called when penugasan status changes (e.g., from FINISH back to PROCESS)
     */
    public static function updatePenugasanPoints($penugasanId, $newStatus, $userId)
    {
        $jenisKinerja = JenisKinerja::where('nama', 'Menyelesaikan Penugasan Kerja')->first();
        if (!$jenisKinerja) {
            return;
        }

        $existingPoint = LaporanKinerja::where('reference', 'App\Models\Penugasan')
            ->where('reference_id', $penugasanId)
            ->first();

        if ($newStatus === 'FINISH') {
            // Add point if not exists
            if (!$existingPoint) {
                LaporanKinerja::create([
                    'user_id' => $userId,
                    'tanggal' => date('Y-m-d'),
                    'jenis_kinerja_id' => $jenisKinerja->id,
                    'nilai' => $jenisKinerja->bobot,
                    'penilaian_berjalan' => 0, // Will be recalculated
                    'reference' => 'App\Models\Penugasan',
                    'reference_id' => $penugasanId,
                ]);
                self::recalculateUserPoints($userId);
            }
        } else {
            // Remove point if exists and status is not FINISH
            if ($existingPoint) {
                $existingPoint->delete();
                self::recalculateUserPoints($userId);
            }
        }
    }

    /**
     * Update rapat attendance points
     */
    public static function updateRapatPoints($rapatId, $userId, $attended = true)
    {
        $jenisKinerja = JenisKinerja::where('nama', 'Menghadiri Pertemuan')->first();
        if (!$jenisKinerja) {
            return;
        }

        $existingPoint = LaporanKinerja::where('reference', 'App\Models\Rapat')
            ->where('reference_id', $rapatId)
            ->where('user_id', $userId)
            ->first();

        if ($attended) {
            // Add point if not exists
            if (!$existingPoint) {
                LaporanKinerja::create([
                    'user_id' => $userId,
                    'tanggal' => date('Y-m-d'),
                    'jenis_kinerja_id' => $jenisKinerja->id,
                    'nilai' => $jenisKinerja->bobot,
                    'penilaian_berjalan' => 0, // Will be recalculated
                    'reference' => 'App\Models\Rapat',
                    'reference_id' => $rapatId,
                ]);
                self::recalculateUserPoints($userId);
            }
        } else {
            // Remove point if attendance is removed
            if ($existingPoint) {
                $existingPoint->delete();
                self::recalculateUserPoints($userId);
            }
        }
    }
}
