<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddAttendanceArchiveFieldsAndIncompleteKpi extends Migration
{
    public function up()
    {
        Schema::table('mapping_shifts', function (Blueprint $table) {
            $table->string('attendance_unique_key', 80)->nullable()->after('tanggal');
            $table->unsignedBigInteger('merged_into_id')->nullable()->after('attendance_unique_key');
            $table->timestamp('merged_at')->nullable()->after('merged_into_id');
            $table->text('merge_note')->nullable()->after('merged_at');

            $table->index(['user_id', 'tanggal', 'merged_into_id'], 'mapping_shift_active_lookup_index');
            $table->index('merged_into_id', 'mapping_shift_merged_into_index');
        });

        if (DB::table('jenis_kinerjas')->where('nama', 'Absensi Tidak Lengkap')->exists()) {
            DB::table('jenis_kinerjas')
                ->where('nama', 'Absensi Tidak Lengkap')
                ->update([
                    'bobot' => 0,
                    'detail' => 'Jejak KPI netral untuk data absensi smart import yang belum memiliki jam masuk dan jam pulang.',
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('jenis_kinerjas')->insert([
                'nama' => 'Absensi Tidak Lengkap',
                'bobot' => 0,
                'detail' => 'Jejak KPI netral untuk data absensi smart import yang belum memiliki jam masuk dan jam pulang.',
                'updated_at' => now(),
                'created_at' => now(),
            ]);
        }

        $this->archiveExistingDuplicateAttendances();
        $this->fillAttendanceKeys();

        Schema::table('mapping_shifts', function (Blueprint $table) {
            $table->unique('attendance_unique_key', 'mapping_shift_attendance_key_unique');
        });
    }

    public function down()
    {
        Schema::table('mapping_shifts', function (Blueprint $table) {
            $table->dropUnique('mapping_shift_attendance_key_unique');
            $table->dropIndex('mapping_shift_active_lookup_index');
            $table->dropIndex('mapping_shift_merged_into_index');
            $table->dropColumn([
                'attendance_unique_key',
                'merged_into_id',
                'merged_at',
                'merge_note',
            ]);
        });

        DB::table('jenis_kinerjas')
            ->where('nama', 'Absensi Tidak Lengkap')
            ->where('bobot', 0)
            ->delete();
    }

    private function archiveExistingDuplicateAttendances(): void
    {
        $groups = DB::table('mapping_shifts')
            ->select('user_id', 'tanggal', DB::raw('COUNT(*) as total'))
            ->whereNull('merged_into_id')
            ->whereNotNull('user_id')
            ->whereNotNull('tanggal')
            ->groupBy('user_id', 'tanggal')
            ->having('total', '>', 1)
            ->get();

        foreach ($groups as $group) {
            $rows = DB::table('mapping_shifts')
                ->where('user_id', $group->user_id)
                ->where('tanggal', $group->tanggal)
                ->whereNull('merged_into_id')
                ->orderBy('id')
                ->get();

            if ($rows->count() <= 1) {
                continue;
            }

            $canonical = $rows->sortByDesc(fn($row) => $this->attendanceRowScore($row))->first();
            $canonicalData = (array) $canonical;
            $duplicateIds = [];

            foreach ($rows as $row) {
                if ((int) $row->id === (int) $canonical->id) {
                    continue;
                }

                $duplicateIds[] = $row->id;
                $canonicalData = $this->mergeAttendanceRowData($canonicalData, (array) $row);
            }

            if ($this->hasClock($canonicalData) && in_array($canonicalData['status_absen'] ?? '', [null, '', 'Tidak Masuk', 'Alpha', 'Alfa'], true)) {
                $canonicalData['status_absen'] = 'Masuk';
            }

            $now = now();
            $canonicalUpdate = $this->onlyAttendanceColumns($canonicalData);
            $canonicalUpdate['attendance_unique_key'] = $this->attendanceKey($group->user_id, $group->tanggal);
            $canonicalUpdate['updated_at'] = $now;

            DB::table('mapping_shifts')
                ->where('id', $canonical->id)
                ->update($canonicalUpdate);

            DB::table('mapping_shifts')
                ->whereIn('id', $duplicateIds)
                ->update([
                    'attendance_unique_key' => null,
                    'merged_into_id' => $canonical->id,
                    'merged_at' => $now,
                    'merge_note' => 'Diarsipkan otomatis karena duplikat pegawai dan tanggal yang sama.',
                    'updated_at' => $now,
                ]);

            DB::table('laporan_kinerjas')
                ->where('reference', 'App\\Models\\MappingShift')
                ->whereIn('reference_id', $duplicateIds)
                ->update([
                    'reference_id' => $canonical->id,
                    'updated_at' => $now,
                ]);
        }
    }

    private function fillAttendanceKeys(): void
    {
        DB::table('mapping_shifts')
            ->whereNull('merged_into_id')
            ->whereNotNull('user_id')
            ->whereNotNull('tanggal')
            ->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    DB::table('mapping_shifts')
                        ->where('id', $row->id)
                        ->update([
                            'attendance_unique_key' => $this->attendanceKey($row->user_id, $row->tanggal),
                        ]);
                }
            });
    }

    private function attendanceRowScore($row): int
    {
        return ($this->isFilled($row->jam_absen ?? null) ? 30 : 0)
            + ($this->isFilled($row->jam_pulang ?? null) ? 30 : 0)
            + ($this->isFilled($row->status_absen ?? null) ? 5 : 0)
            + ($this->isFilled($row->keterangan_masuk ?? null) ? 2 : 0)
            + ($this->isFilled($row->keterangan_pulang ?? null) ? 2 : 0);
    }

    private function mergeAttendanceRowData(array $canonical, array $duplicate): array
    {
        foreach ($this->mergeableColumns() as $column) {
            if (!$this->isFilled($canonical[$column] ?? null) && $this->isFilled($duplicate[$column] ?? null)) {
                $canonical[$column] = $duplicate[$column];
            }
        }

        return $canonical;
    }

    private function onlyAttendanceColumns(array $data): array
    {
        return array_intersect_key($data, array_flip($this->mergeableColumns()));
    }

    private function mergeableColumns(): array
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

    private function hasClock(array $data): bool
    {
        return $this->isFilled($data['jam_absen'] ?? null) || $this->isFilled($data['jam_pulang'] ?? null);
    }

    private function isFilled($value): bool
    {
        return $value !== null && $value !== '';
    }

    private function attendanceKey($userId, $tanggal): string
    {
        return 'mapping-shift:' . $userId . ':' . $tanggal;
    }
}
