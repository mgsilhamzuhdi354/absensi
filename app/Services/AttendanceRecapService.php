<?php

namespace App\Services;

use App\Models\dinasLuar;
use App\Models\Lembur;
use App\Models\MappingShift;
use Illuminate\Support\Collection;

class AttendanceRecapService
{
    private const PRESENT_STATUSES = ['Masuk', 'Izin Telat', 'Izin Pulang Cepat'];
    private const KNOWN_STATUSES = ['Masuk', 'Izin Telat', 'Izin Pulang Cepat', 'Libur', 'Cuti', 'Izin Masuk', 'Sakit', 'Tidak Masuk', 'Alpha', 'Alfa'];
    private const ABSENT_STATUSES = ['Tidak Masuk', 'Alpha', 'Alfa'];

    public function summaryForUser(int $userId, string $tanggalMulai, string $tanggalAkhir): array
    {
        return $this->summariesForUsers(collect([$userId]), $tanggalMulai, $tanggalAkhir)[$userId]
            ?? $this->emptySummary();
    }

    public function summariesForUsers($usersOrIds, string $tanggalMulai, string $tanggalAkhir): array
    {
        $userIds = $this->extractUserIds($usersOrIds);
        if ($userIds->isEmpty()) {
            return [];
        }

        $mappingByUser = MappingShift::whereIn('user_id', $userIds)
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
            ->get()
            ->groupBy('user_id');

        $dinasByUser = dinasLuar::whereIn('user_id', $userIds)
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
            ->selectRaw('user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $lemburByUser = Lembur::whereIn('user_id', $userIds)
            ->where('status', 'Approved')
            ->whereBetween('tanggal', [$tanggalMulai, $tanggalAkhir])
            ->selectRaw('user_id, COALESCE(SUM(total_lembur), 0) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $summaries = [];
        foreach ($userIds as $userId) {
            $summaries[$userId] = $this->buildSummary(
                $mappingByUser->get($userId, collect()),
                (int) ($dinasByUser[$userId] ?? 0),
                (int) ($lemburByUser[$userId] ?? 0)
            );
        }

        return $summaries;
    }

    private function buildSummary(Collection $mappingRows, int $totalDinasLuar, int $totalLembur): array
    {
        $cuti = $this->countStatus($mappingRows, 'Cuti');
        $izinMasuk = $this->countStatus($mappingRows, 'Izin Masuk');
        $sakit = $this->countStatus($mappingRows, 'Sakit');
        $izinTelat = $this->countStatus($mappingRows, 'Izin Telat');
        $izinPulangCepat = $this->countStatus($mappingRows, 'Izin Pulang Cepat');
        $masuk = $this->countStatus($mappingRows, 'Masuk');
        $libur = $this->countStatus($mappingRows, 'Libur');
        $totalHadir = $mappingRows->whereIn('status_absen', self::PRESENT_STATUSES)->count();
        $totalAlfa = $mappingRows->filter(function ($row) {
            $status = trim((string) $row->status_absen);

            return in_array($status, self::ABSENT_STATUSES, true)
                || !in_array($status, self::KNOWN_STATUSES, true);
        })->count();

        $totalTelat = (int) $mappingRows->sum('telat');
        $jumlahTelat = $mappingRows->where('telat', '>', 0)->count();
        $totalPulangCepat = (int) $mappingRows->sum('pulang_cepat');
        $jumlahPulangCepat = $mappingRows->where('pulang_cepat', '>', 0)->count();
        $hariKerja = max(0, $mappingRows->count() - $libur);
        $persentase = $hariKerja > 0 ? ($totalHadir / $hariKerja) * 100 : 0;
        $persentase = min(100, max(0, $persentase));

        return [
            'cuti' => $cuti,
            'izin_masuk' => $izinMasuk,
            'sakit' => $sakit,
            'izin_telat' => $izinTelat,
            'izin_pulang_cepat' => $izinPulangCepat,
            'masuk' => $masuk,
            'total_hadir' => $totalHadir,
            'total_dinas_luar' => $totalDinasLuar,
            'total_alfa' => $totalAlfa,
            'libur' => $libur,
            'hari_kerja' => $hariKerja,
            'total_telat' => $totalTelat,
            'jumlah_telat' => $jumlahTelat,
            'total_pulang_cepat' => $totalPulangCepat,
            'jumlah_pulang_cepat' => $jumlahPulangCepat,
            'total_lembur' => $totalLembur,
            'telat_duration' => $this->durationParts($totalTelat),
            'pulang_cepat_duration' => $this->durationParts($totalPulangCepat),
            'lembur_duration' => $this->durationParts($totalLembur),
            'persentase_kehadiran' => $persentase,
        ];
    }

    private function countStatus(Collection $rows, string $status): int
    {
        return $rows->where('status_absen', $status)->count();
    }

    private function durationParts(int $seconds): array
    {
        $hours = (int) floor($seconds / 3600);
        $minutes = (int) floor(($seconds - ($hours * 3600)) / 60);

        return [
            'hours' => $hours,
            'minutes' => $minutes,
            'label' => $hours . ' Jam ' . $minutes . ' Menit',
        ];
    }

    private function emptySummary(): array
    {
        return $this->buildSummary(collect(), 0, 0);
    }

    private function extractUserIds($usersOrIds): Collection
    {
        return collect($usersOrIds)
            ->map(fn($item) => is_object($item) ? $item->id : $item)
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();
    }
}
