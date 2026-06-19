<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Shift;
use App\Models\MappingShift;
use App\Models\dinasLuar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Services\SmartAbsenParser;
use App\Services\KinerjaService;

class SmartAbsenImportController extends Controller
{
    private const FILE_FIELD = 'file_absen';
    private const TEMP_IMPORT_DIRECTORY = 'temp_import';
    private const TARGET_MAPPING_SHIFT = 'mapping_shifts';
    private const TARGET_DINAS_LUAR = 'dinas_luars';
    private const SOURCE_MACHINE_PACKAGE = 'machine_package';
    private const SOURCE_MACHINE_SUMMARY = 'machine_package_summary';
    private const SOURCE_SHIFT_SCHEDULE = 'shift_schedule';
    private const DEFAULT_ATTENDANCE_STATUS = 'Tidak Masuk';

    private array $officeLocationCache = [];

    /**
     * Tampilkan halaman Smart Import
     */
    public function showForm()
    {
        return view('absen.smart-import', [
            'title'  => 'Smart Import Absensi',
            'shifts' => Shift::all(),
            'users'  => User::orderBy('name', 'ASC')->get(),
        ]);
    }

    /**
     * Upload dan preview file, lalu kembalikan JSON preview.
     */
    public function preview(Request $request)
    {
        $request->validate(array_merge($this->uploadedFileRules($request), [
            'shift_id' => 'required|exists:shifts,id',
        ]));

        try {
            $shift = Shift::findOrFail($request->shift_id);
            $parser = new SmartAbsenParser();

            $tempFiles = $this->storeTemporaryFiles($this->uploadedFiles($request));
            $paths = $tempFiles['paths'];
            $fullPaths = $tempFiles['full_paths'];

            $rows = $parser->parseExcel($fullPaths[0]);

            if (count($rows) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'File kosong atau hanya berisi header.'
                ], 422);
            }

            $users = User::orderBy('name', 'ASC')->get();
            $machineType = $parser->machineFileType($rows);
            $workbookTypes = $this->workbookTypes($parser, $fullPaths);
            $machineMode = $this->isMachineMode($fullPaths, $machineType, $workbookTypes);
            $machineMeta = $this->emptyMachineMeta();

            if ($machineMode) {
                $machineResult = $parser->processMachineFiles(
                    $fullPaths,
                    $users,
                    $shift->id,
                    $shift->jam_masuk,
                    $shift->jam_keluar
                );

                $results = $machineResult['results'];
                $rawPreview = $machineResult['raw_preview'];
                $headerRow = $rawPreview['headers'];
                $columnMap = [self::SOURCE_MACHINE_PACKAGE => true];
                $machineMeta = [
                    'files' => $machineResult['files'],
                    'warnings' => $machineResult['warnings'],
                    'date_range' => $machineResult['date_range'],
                ];
            } else {
                // Deteksi header row
                $headerRowIndex = $parser->findHeaderRow($rows);
                $headerRow = $rows[$headerRowIndex];

                // Deteksi kolom
                $columnMap = $parser->detectColumns($headerRow);
                $rawPreview = $parser->buildRawPreview($rows, $headerRowIndex);

                if ($columnMap['nama'] === null && $columnMap['employee_id'] === null) {
                    $results = $this->buildInvalidPreviewRows(
                        $rawPreview,
                        $shift->id,
                        'Kolom identitas karyawan tidak ditemukan. Pastikan ada kolom Nama/Name/Karyawan atau Employee ID/NIP/PIN.'
                    );
                } else {
                    $results = $parser->processRows(
                        $rows,
                        $headerRowIndex,
                        $columnMap,
                        $users,
                        $shift->id,
                        $shift->jam_masuk,
                        $shift->jam_keluar
                    );
                    $results = $this->attachRawColumnsToResults($results, $rawPreview);
                }
            }

            $results = $this->markPreviewActions($results);

            $stats = $this->previewStats($results, $rawPreview, $paths, $machineMeta);
            $this->rememberTemporaryFiles($paths);

            return response()->json([
                'success'     => true,
                'headers'     => $headerRow,
                'column_map'  => $columnMap,
                'raw_preview' => $rawPreview,
                'preview'     => $results,
                'stats'       => $stats,
                'shift_name'  => $shift->nama_shift . ' (' . $shift->jam_masuk . ' - ' . $shift->jam_keluar . ')',
                'machine'     => $machineMeta,
            ]);

        } catch (\Exception $e) {
            \Log::error('Smart Import Preview Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses file: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Import data yang sudah di-preview ke mapping_shifts
     */
    public function importData(Request $request)
    {
        $importRows = $this->validatedImportRows($request);
        $importDateRange = $this->importDateRange($importRows);
        $clockRows = $this->clockRowsCount($importRows);
        $created = 0;
        $updated = 0;
        $dinasCreated = 0;
        $dinasUpdated = 0;
        $skipped = 0;
        $errors  = [];

        DB::beginTransaction();
        try {
            foreach ($importRows as $index => $row) {
                try {
                    if ($this->isMachineSummaryRow($row)) {
                        $errors[] = "Baris " . ($index + 1) . ": File laporan rekap tidak bisa diimport sebagai data absensi. Upload Catatan Kehadiran Karyawan.";
                        $skipped++;
                        continue;
                    }

                    $target = $this->targetImportFlags($row);

                    if ($target['mapping']) {
                        $this->importMappingShift($row, $created, $updated);
                    }

                    if ($target['dinas']) {
                        $this->importDinasLuar($row, $dinasCreated, $dinasUpdated);
                    }
                } catch (\Exception $e) {
                    $errors[] = "Baris " . ($index + 1) . ": " . $e->getMessage();
                    $skipped++;
                }
            }

            DB::commit();

            $this->cleanupTemporaryFiles();

            return response()->json([
                'success' => true,
                'message' => 'Import berhasil!',
                'stats'   => [
                    'created' => $created,
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'errors'  => $errors,
                    'clock_rows' => $clockRows,
                    'dinas_created' => $dinasCreated,
                    'dinas_updated' => $dinasUpdated,
                ],
                'date_range' => $importDateRange,
                'data_absen_url' => $importDateRange
                    ? url('/data-absen') . '?' . http_build_query([
                        'mulai' => $importDateRange['start'],
                        'akhir' => $importDateRange['end'],
                    ])
                    : url('/data-absen'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Smart Import Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengimport data: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function uploadedFileRules(Request $request): array
    {
        if (is_array($request->file(self::FILE_FIELD))) {
            return [
                self::FILE_FIELD => 'required|array|min:1',
                self::FILE_FIELD . '.*' => 'file|mimes:xlsx,xls,csv|max:10240',
            ];
        }

        return [
            self::FILE_FIELD => 'required|file|mimes:xlsx,xls,csv|max:10240',
        ];
    }

    private function uploadedFiles(Request $request): array
    {
        $uploadedFiles = $request->file(self::FILE_FIELD);

        return is_array($uploadedFiles) ? $uploadedFiles : [$uploadedFiles];
    }

    private function storeTemporaryFiles(array $uploadedFiles): array
    {
        $paths = [];
        $fullPaths = [];

        foreach ($uploadedFiles as $uploadedFile) {
            $path = $uploadedFile->store(self::TEMP_IMPORT_DIRECTORY, 'local');
            $paths[] = $path;
            $fullPaths[] = storage_path('app/' . $path);
        }

        return [
            'paths' => $paths,
            'full_paths' => $fullPaths,
        ];
    }

    private function workbookTypes(SmartAbsenParser $parser, array $fullPaths): array
    {
        $types = [];

        foreach ($fullPaths as $fullPath) {
            $types = array_merge($types, $parser->workbookFileTypes($fullPath));
        }

        return $types;
    }

    private function isMachineMode(array $fullPaths, string $machineType, array $workbookTypes): bool
    {
        return count($fullPaths) > 1
            || $machineType !== 'generic'
            || collect($workbookTypes)->contains(fn($type) => $type !== 'generic');
    }

    private function emptyMachineMeta(): array
    {
        return [
            'files' => [],
            'warnings' => [],
            'date_range' => [],
        ];
    }

    private function markPreviewActions(array $results): array
    {
        return array_map(function (array $row) {
            if ($row['user_id'] && $row['tanggal']) {
                $existing = MappingShift::where('user_id', $row['user_id'])
                    ->where('tanggal', $row['tanggal'])
                    ->first();
                $row['existing_id'] = $existing ? $existing->id : null;
                $row['action'] = $existing ? 'update' : 'create';

                return $row;
            }

            $row['existing_id'] = null;
            $row['action'] = 'skip';

            return $row;
        }, $results);
    }

    private function previewStats(array $results, array $rawPreview, array $paths, array $machineMeta): array
    {
        return [
            'total'      => count($results),
            'valid'      => count(array_filter($results, fn($row) => $row['valid'] && in_array($row['match_type'], ['exact', 'employee_id'], true))),
            'fuzzy'      => count(array_filter($results, fn($row) => $row['valid'] && $row['match_type'] === 'fuzzy')),
            'invalid'    => count(array_filter($results, fn($row) => !$row['valid'])),
            'will_create'=> count(array_filter($results, fn($row) => $row['action'] === 'create')),
            'will_update'=> count(array_filter($results, fn($row) => $row['action'] === 'update')),
            'raw_rows'   => $rawPreview['total_rows'],
            'raw_columns'=> $rawPreview['total_columns'],
            'files'      => count($paths),
            'warnings'   => count($machineMeta['warnings']),
        ];
    }

    private function rememberTemporaryFiles(array $paths): void
    {
        session([
            'smart_import_temp_path' => $paths[0] ?? null,
            'smart_import_temp_paths' => $paths,
        ]);
    }

    private function validatedImportRows(Request $request): array
    {
        $request->validate([
            'import_rows'   => 'required|array|min:1',
            'import_rows.*.user_id'      => 'required|integer|exists:users,id',
            'import_rows.*.tanggal'      => 'required|date',
            'import_rows.*.jam_absen'    => 'nullable',
            'import_rows.*.jam_pulang'   => 'nullable',
            'import_rows.*.status_absen' => 'required',
            'import_rows.*.telat'        => 'nullable|integer',
            'import_rows.*.pulang_cepat' => 'nullable|integer',
            'import_rows.*.shift_id'     => 'required|integer|exists:shifts,id',
            'import_rows.*.target_table' => 'nullable|string',
            'import_rows.*.source_priority' => 'nullable|integer',
            'import_rows.*.special_type' => 'nullable|string',
        ]);

        return $request->import_rows;
    }

    private function importDateRange(array $importRows): ?array
    {
        $importDates = collect($importRows)->pluck('tanggal')->filter()->sort()->values();

        if ($importDates->isEmpty()) {
            return null;
        }

        return [
            'start' => $importDates->first(),
            'end' => $importDates->last(),
        ];
    }

    private function clockRowsCount(array $importRows): int
    {
        return collect($importRows)->filter(function (array $row) {
            return !empty($row['jam_absen']) || !empty($row['jam_pulang']);
        })->count();
    }

    private function isMachineSummaryRow(array $row): bool
    {
        return ($row['source_format'] ?? '') === self::SOURCE_MACHINE_SUMMARY;
    }

    private function targetImportFlags(array $row): array
    {
        $targetTable = $row['target_table'] ?? self::TARGET_MAPPING_SHIFT;

        return [
            'mapping' => $targetTable === '' || str_contains($targetTable, self::TARGET_MAPPING_SHIFT),
            'dinas' => str_contains($targetTable, self::TARGET_DINAS_LUAR),
        ];
    }

    private function importMappingShift(array $row, int &$created, int &$updated): void
    {
        $existing = MappingShift::where('user_id', $row['user_id'])
            ->where('tanggal', $row['tanggal'])
            ->first();

        $data = $this->buildMappingShiftData($row);

        if ($existing) {
            $existing->update($this->mergeWithExistingAttendance($data, $existing, $row));
            KinerjaService::updateAttendancePoints($existing->id, $row['user_id']);
            $updated++;

            return;
        }

        $newShift = MappingShift::create($this->withDefaultSystemFields($data));
        KinerjaService::updateAttendancePoints($newShift->id, $row['user_id']);
        $created++;
    }

    private function importDinasLuar(array $row, int &$dinasCreated, int &$dinasUpdated): void
    {
        $dinas = $this->upsertDinasLuar($row);

        if ($dinas->wasRecentlyCreated) {
            $dinasCreated++;
            return;
        }

        $dinasUpdated++;
    }

    private function cleanupTemporaryFiles(): void
    {
        $tempPaths = session('smart_import_temp_paths', []);
        $legacyTempPath = session('smart_import_temp_path');

        if ($legacyTempPath) {
            $tempPaths[] = $legacyTempPath;
        }

        foreach (array_unique(array_filter($tempPaths)) as $tempPath) {
            if (Storage::disk('local')->exists($tempPath)) {
                Storage::disk('local')->delete($tempPath);
            }
        }

        session()->forget(['smart_import_temp_path', 'smart_import_temp_paths']);
    }

    private function attachRawColumnsToResults(array $results, array $rawPreview): array
    {
        $rawRows = collect($rawPreview['rows'])->keyBy('row_index');

        return array_map(function (array $row) use ($rawRows) {
            $rawRow = $rawRows->get($row['row_index']);
            $row['raw_columns'] = $rawRow['columns'] ?? ($row['raw_columns'] ?? []);

            return $row;
        }, $results);
    }

    private function buildInvalidPreviewRows(array $rawPreview, int $shiftId, string $message): array
    {
        return array_map(function (array $rawRow) use ($shiftId, $message) {
            return [
                'preview_key' => (string) $rawRow['row_index'],
                'row_index' => $rawRow['row_index'],
                'source_format' => 'unsupported',
                'raw_columns' => $rawRow['columns'],
                'raw_nama' => '',
                'raw_employee_id' => '',
                'raw_tanggal' => '',
                'raw_masuk' => '',
                'raw_pulang' => '',
                'raw_status' => '',
                'tanggal' => null,
                'jam_absen' => null,
                'jam_pulang' => null,
                'status_absen' => self::DEFAULT_ATTENDANCE_STATUS,
                'telat' => 0,
                'pulang_cepat' => 0,
                'shift_id' => $shiftId,
                'user_id' => null,
                'user_name' => null,
                'confidence' => 0,
                'match_type' => 'not_found',
                'valid' => false,
                'errors' => [$message],
                'existing_id' => null,
                'action' => 'skip',
                'source_sheet' => '',
                'source_priority' => 0,
                'source_confidence' => 0,
                'target_table' => self::TARGET_MAPPING_SHIFT,
                'conflict_notes' => [],
                'special_type' => null,
            ];
        }, $rawPreview['rows']);
    }

    private function buildMappingShiftData(array $row): array
    {
        $sourceFormat = $row['source_format'] ?? '';
        $keterangan = 'Smart Import Absensi';
        if ($sourceFormat === self::SOURCE_MACHINE_PACKAGE) {
            $keterangan = 'Smart Import Absensi (Scan Mesin)';
        } elseif ($sourceFormat === self::SOURCE_SHIFT_SCHEDULE) {
            $keterangan = 'Smart Import Absensi (Jadwal Mesin)';
        }

        $hasCheckIn = !empty($row['jam_absen']);
        $hasCheckOut = !empty($row['jam_pulang']);
        $officeLocation = $this->officeLocationForUser((int) $row['user_id']);

        $data = [
            'user_id'      => $row['user_id'],
            'shift_id'     => $row['shift_id'],
            'tanggal'      => $row['tanggal'],
            'jam_absen'    => !empty($row['jam_absen']) ? $row['jam_absen'] : null,
            'jam_pulang'   => !empty($row['jam_pulang']) ? $row['jam_pulang'] : null,
            'status_absen' => $row['status_absen'],
            'telat'        => $row['telat'] ?? 0,
            'pulang_cepat' => $row['pulang_cepat'] ?? 0,
            'keterangan_masuk' => $keterangan,
            'keterangan_pulang' => !empty($row['jam_pulang']) ? $keterangan : null,
        ];

        if ($hasCheckIn && $officeLocation) {
            $data['lat_absen'] = $officeLocation['lat'];
            $data['long_absen'] = $officeLocation['long'];
            $data['jarak_masuk'] = 0;
        }

        if ($hasCheckOut && $officeLocation) {
            $data['lat_pulang'] = $officeLocation['lat'];
            $data['long_pulang'] = $officeLocation['long'];
            $data['jarak_pulang'] = 0;
        }

        return $data;
    }

    private function upsertDinasLuar(array $row): dinasLuar
    {
        return dinasLuar::updateOrCreate(
            [
                'user_id' => $row['user_id'],
                'tanggal' => $row['tanggal'],
                'shift_id' => $row['shift_id'],
            ],
            $this->buildDinasLuarData($row)
        );
    }

    private function buildDinasLuarData(array $row): array
    {
        $hasCheckIn = !empty($row['jam_absen']);
        $hasCheckOut = !empty($row['jam_pulang']);
        $officeLocation = $this->officeLocationForUser((int) $row['user_id']);
        $data = [
            'jam_absen' => $hasCheckIn ? $row['jam_absen'] : null,
            'jam_pulang' => $hasCheckOut ? $row['jam_pulang'] : null,
            'telat' => $row['telat'] ?? 0,
            'pulang_cepat' => $row['pulang_cepat'] ?? 0,
            'status_absen' => $hasCheckIn ? 'Masuk' : 'Izin Masuk',
            'lokasi' => 'Smart Import Absensi',
        ];

        if ($hasCheckIn && $officeLocation) {
            $data['lat_absen'] = $officeLocation['lat'];
            $data['long_absen'] = $officeLocation['long'];
        }

        if ($hasCheckOut && $officeLocation) {
            $data['lat_pulang'] = $officeLocation['lat'];
            $data['long_pulang'] = $officeLocation['long'];
        }

        return $data;
    }

    private function mergeWithExistingAttendance(array $data, MappingShift $existing, array $row = []): array
    {
        $shouldClearEmptyAttendance = $this->shouldClearEmptyAttendance($row);

        if ($data['jam_absen'] === null && $existing->jam_absen !== null && !$shouldClearEmptyAttendance) {
            unset($data['jam_absen'], $data['telat'], $data['keterangan_masuk']);
        }

        if ($data['jam_pulang'] === null && $existing->jam_pulang !== null && !$shouldClearEmptyAttendance) {
            unset($data['jam_pulang'], $data['pulang_cepat'], $data['keterangan_pulang']);
        }

        if ($shouldClearEmptyAttendance) {
            if (($data['jam_absen'] ?? null) === null) {
                $data['lat_absen'] = 0;
                $data['long_absen'] = 0;
                $data['jarak_masuk'] = 0;
            }

            if (($data['jam_pulang'] ?? null) === null) {
                $data['lat_pulang'] = 0;
                $data['long_pulang'] = 0;
                $data['jarak_pulang'] = 0;
            }
        }

        return $data;
    }

    private function shouldClearEmptyAttendance(array $row): bool
    {
        $priority = (int) ($row['source_priority'] ?? 0);
        $status = $row['status_absen'] ?? '';

        return $priority >= 60
            && empty($row['jam_absen'])
            && empty($row['jam_pulang'])
            && in_array($status, ['Libur', 'Cuti', 'Izin Masuk', 'Sakit', 'Tidak Masuk'], true);
    }

    private function withDefaultSystemFields(array $data): array
    {
        return array_merge([
            'lock_location' => 0,
            'lat_absen' => 0,
            'long_absen' => 0,
            'lat_pulang' => 0,
            'long_pulang' => 0,
            'jarak_masuk' => 0,
            'jarak_pulang' => 0,
        ], $data);
    }

    private function officeLocationForUser(int $userId): ?array
    {
        if (array_key_exists($userId, $this->officeLocationCache)) {
            return $this->officeLocationCache[$userId];
        }

        $user = User::with('Lokasi:id,lat_kantor,long_kantor')->find($userId);
        $lokasi = $user ? $user->Lokasi : null;
        if (!$lokasi || $lokasi->lat_kantor === null || $lokasi->long_kantor === null) {
            return $this->officeLocationCache[$userId] = null;
        }

        return $this->officeLocationCache[$userId] = [
            'lat' => $lokasi->lat_kantor,
            'long' => $lokasi->long_kantor,
        ];
    }
}
