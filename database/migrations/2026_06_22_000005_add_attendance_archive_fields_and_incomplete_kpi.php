<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddAttendanceArchiveFieldsAndIncompleteKpi extends Migration
{
    public function up()
    {
        $this->ensureMappingShiftColumns();
        $this->ensureIndex('mapping_shifts', ['user_id', 'tanggal', 'merged_into_id'], 'mapping_shift_active_lookup_index');
        $this->ensureIndex('mapping_shifts', ['merged_into_id'], 'mapping_shift_merged_into_index');

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

        $this->ensureIndex('mapping_shifts', ['attendance_unique_key'], 'mapping_shift_attendance_key_unique', true);
    }

    public function down()
    {
        Schema::table('mapping_shifts', function (Blueprint $table) {
            if ($this->indexExists('mapping_shifts', 'mapping_shift_attendance_key_unique')) {
                $table->dropUnique('mapping_shift_attendance_key_unique');
            }
            if ($this->indexExists('mapping_shifts', 'mapping_shift_active_lookup_index')) {
                $table->dropIndex('mapping_shift_active_lookup_index');
            }
            if ($this->indexExists('mapping_shifts', 'mapping_shift_merged_into_index')) {
                $table->dropIndex('mapping_shift_merged_into_index');
            }
        });

        foreach (['attendance_unique_key', 'merged_into_id', 'merged_at', 'merge_note'] as $column) {
            if (Schema::hasColumn('mapping_shifts', $column)) {
                Schema::table('mapping_shifts', function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });
            }
        }

        DB::table('jenis_kinerjas')
            ->where('nama', 'Absensi Tidak Lengkap')
            ->where('bobot', 0)
            ->delete();
    }

    private function ensureMappingShiftColumns(): void
    {
        if (!Schema::hasColumn('mapping_shifts', 'attendance_unique_key')) {
            Schema::table('mapping_shifts', function (Blueprint $table) {
                $table->string('attendance_unique_key', 80)->nullable()->after('tanggal');
            });
        }

        if (!Schema::hasColumn('mapping_shifts', 'merged_into_id')) {
            Schema::table('mapping_shifts', function (Blueprint $table) {
                $table->unsignedBigInteger('merged_into_id')->nullable()->after('attendance_unique_key');
            });
        }

        if (!Schema::hasColumn('mapping_shifts', 'merged_at')) {
            Schema::table('mapping_shifts', function (Blueprint $table) {
                $table->timestamp('merged_at')->nullable()->after('merged_into_id');
            });
        }

        if (!Schema::hasColumn('mapping_shifts', 'merge_note')) {
            Schema::table('mapping_shifts', function (Blueprint $table) {
                $table->text('merge_note')->nullable()->after('merged_at');
            });
        }
    }

    private function ensureIndex(string $tableName, array $columns, string $indexName, bool $unique = false): void
    {
        if ($this->indexExists($tableName, $indexName)) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName, $unique) {
            if ($unique) {
                $table->unique($columns, $indexName);
            } else {
                $table->index($columns, $indexName);
            }
        });
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('" . str_replace("'", "''", $tableName) . "')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'mysql') {
            return count(DB::select("SHOW INDEX FROM `{$tableName}` WHERE Key_name = ?", [$indexName])) > 0;
        }

        return false;
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
        $driver = DB::connection()->getDriverName();
        $expression = $driver === 'mysql'
            ? "CONCAT('mapping-shift:', user_id, ':', tanggal)"
            : "'mapping-shift:' || user_id || ':' || tanggal";

        DB::statement("
            UPDATE mapping_shifts
            SET attendance_unique_key = {$expression}
            WHERE merged_into_id IS NULL
              AND user_id IS NOT NULL
              AND tanggal IS NOT NULL
              AND attendance_unique_key IS NULL
        ");
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
