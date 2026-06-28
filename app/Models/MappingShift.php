<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class MappingShift extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public const PEGAWAI_STATUS_AKTIF = 'aktif';
    public const PEGAWAI_STATUS_KELUAR = 'keluar';

    public function User()
    {
        return $this->belongsTo(User::class);
    }

    public function Shift()
    {
        return $this->belongsTo(Shift::class);
    }

    public static function attendanceUniqueKey($userId, $tanggal): string
    {
        return 'mapping-shift:' . $userId . ':' . $tanggal;
    }

    public static function activePegawaiKeluarUserIds()
    {
        return PegawaiKeluar::select('user_id')
            ->where('status', PegawaiKeluar::STATUS_APPROVED);
    }

    public static function normalizedPegawaiStatus(?string $status): string
    {
        return $status === self::PEGAWAI_STATUS_KELUAR
            ? self::PEGAWAI_STATUS_KELUAR
            : self::PEGAWAI_STATUS_AKTIF;
    }

    public static function mergeDuplicateAttendancesFor($userId, $tanggal, bool $lockForUpdate = false): ?self
    {
        $query = self::where('user_id', $userId)
            ->where('tanggal', $tanggal)
            ->whereNull('merged_into_id');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $rows = $query->orderBy('id')->get();

        if ($rows->isEmpty()) {
            return null;
        }

        $canonical = self::chooseCanonicalAttendance($rows);
        $duplicates = $rows->reject(fn($row) => (int) $row->id === (int) $canonical->id);

        if ($duplicates->isEmpty()) {
            $canonical->forceFill([
                'attendance_unique_key' => self::attendanceUniqueKey($userId, $tanggal),
            ])->save();

            return $canonical->fresh();
        }

        foreach ($duplicates as $duplicate) {
            $canonical->fill(self::mergedAttendanceValues($canonical, $duplicate));

            LaporanKinerja::where('reference', self::class)
                ->where('reference_id', $duplicate->id)
                ->update(['reference_id' => $canonical->id]);

            $duplicate->forceFill([
                'attendance_unique_key' => null,
                'merged_into_id' => $canonical->id,
                'merged_at' => now(),
                'merge_note' => 'Diarsipkan otomatis karena duplikat pegawai dan tanggal yang sama.',
            ])->save();
        }

        if (self::hasAnyClock($canonical) && in_array($canonical->status_absen, [null, '', 'Tidak Masuk', 'Alpha', 'Alfa'], true)) {
            $canonical->status_absen = 'Masuk';
        }

        $canonical->attendance_unique_key = self::attendanceUniqueKey($userId, $tanggal);
        $canonical->save();

        return $canonical->fresh();
    }

    private static function chooseCanonicalAttendance(Collection $rows): self
    {
        return $rows->sortByDesc(fn($row) => self::attendanceCompletenessScore($row))->first();
    }

    private static function attendanceCompletenessScore(self $row): int
    {
        return (self::filledValue($row->jam_absen) ? 30 : 0)
            + (self::filledValue($row->jam_pulang) ? 30 : 0)
            + (self::filledValue($row->status_absen) ? 5 : 0)
            + (self::filledValue($row->keterangan_masuk) ? 2 : 0)
            + (self::filledValue($row->keterangan_pulang) ? 2 : 0);
    }

    private static function mergedAttendanceValues(self $canonical, self $duplicate): array
    {
        $updates = [];

        foreach (self::mergeableAttendanceColumns() as $column) {
            if (!self::filledValue($canonical->{$column}) && self::filledValue($duplicate->{$column})) {
                $updates[$column] = $duplicate->{$column};
            }
        }

        return $updates;
    }

    private static function mergeableAttendanceColumns(): array
    {
        return [
            'shift_id',
            'jam_absen',
            'telat',
            'lat_absen',
            'long_absen',
            'jarak_masuk',
            'foto_jam_absen',
            'keterangan_masuk',
            'jam_pulang',
            'pulang_cepat',
            'lat_pulang',
            'long_pulang',
            'jarak_pulang',
            'foto_jam_pulang',
            'keterangan_pulang',
            'status_absen',
            'lock_location',
            'jam_masuk_pengajuan',
            'jam_pulang_pengajuan',
            'deskripsi',
            'status_pengajuan',
            'file_pengajuan',
            'komentar',
            'approved_by',
        ];
    }

    private static function hasAnyClock(self $row): bool
    {
        return self::filledValue($row->jam_absen) || self::filledValue($row->jam_pulang);
    }

    private static function filledValue($value): bool
    {
        return $value !== null && $value !== '';
    }

    public static function dataAbsen()
    {
        date_default_timezone_set('Asia/Jakarta');
        $tglskrg = date('Y-m-d');

        $user_id = request()->input('user_id');
        $mulai = request()->input('mulai');
        $akhir = request()->input('akhir');
        $pegawaiStatus = self::normalizedPegawaiStatus(request()->input('pegawai_status'));

        $data_absen = MappingShift::select('mapping_shifts.*', 'users.name')
        ->rightJoin('users', function($join) use ($tglskrg, $mulai, $akhir) {
            $join->on('users.id', '=', 'mapping_shifts.user_id')
                ->whereNull('mapping_shifts.merged_into_id')
                ->when(!$mulai && !$akhir, function ($query) use ($tglskrg) {
                    return $query->where('mapping_shifts.tanggal', '=', $tglskrg);
                })
                ->when($mulai && $akhir, function ($query) use ($mulai, $akhir) {
                    return $query->whereBetween('tanggal', [$mulai, $akhir]);
                });
        })
        ->when(auth()->user()->hasRole('kepala_cabang'), function ($query) {
            return $query->where('users.lokasi_id', auth()->user()->lokasi_id);
        })
        ->when(auth()->user()->is_admin == 'user', function ($query) {
            return $query->where('users.id', auth()->user()->id);
        })
        ->when($user_id, function ($query) use ($user_id) {
            return $query->where('users.id', $user_id);
        })
        ->when($pegawaiStatus === self::PEGAWAI_STATUS_KELUAR, function ($query) {
            return $query->whereIn('users.id', self::activePegawaiKeluarUserIds());
        })
        ->when($pegawaiStatus === self::PEGAWAI_STATUS_AKTIF, function ($query) {
            return $query->whereNotIn('users.id', self::activePegawaiKeluarUserIds());
        })
        ->orderBy('tanggal', 'ASC')
        ->orderBy('users.name', 'ASC');

        return $data_absen;
    }
}
