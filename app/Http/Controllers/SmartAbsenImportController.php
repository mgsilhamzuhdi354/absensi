<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Shift;
use App\Models\MappingShift;
use App\Models\dinasLuar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SmartAbsenParser;
use App\Services\KinerjaService;

class SmartAbsenImportController extends Controller
{
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
     * Upload & preview — parse file, kembalikan JSON preview
     */
    public function preview(Request $request)
    {
        $fileRules = is_array($request->file('file_absen'))
            ? ['file_absen' => 'required|array|min:1', 'file_absen.*' => 'file|mimes:xlsx,xls,csv|max:10240']
            : ['file_absen' => 'required|file|mimes:xlsx,xls,csv|max:10240'];

        $request->validate(array_merge($fileRules, [
            'shift_id' => 'required|exists:shifts,id',
        ]));

        try {
            $shift = Shift::findOrFail($request->shift_id);
            $parser = new SmartAbsenParser();

            $uploadedFiles = $request->file('file_absen');
            $uploadedFiles = is_array($uploadedFiles) ? $uploadedFiles : [$uploadedFiles];

            // Simpan file sementara
            $paths = [];
            $fullPaths = [];
            foreach ($uploadedFiles as $uploadedFile) {
                $path = $uploadedFile->store('temp_import', 'local');
                $paths[] = $path;
                $fullPaths[] = storage_path('app/' . $path);
            }

            // Parse Excel
            $rows = $parser->parseExcel($fullPaths[0]);

            if (count($rows) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'File kosong atau hanya berisi header.'
                ], 422);
            }

            $users = User::orderBy('name', 'ASC')->get();
            $machineType = $parser->machineFileType($rows);
            $workbookTypes = [];
            foreach ($fullPaths as $fullPath) {
                $workbookTypes = array_merge($workbookTypes, $parser->workbookFileTypes($fullPath));
            }
            $machineMode = count($fullPaths) > 1
                || $machineType !== 'generic'
                || collect($workbookTypes)->contains(fn($type) => $type !== 'generic');
            $machineMeta = [
                'files' => [],
                'warnings' => [],
                'date_range' => [],
            ];

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
                $columnMap = ['machine_package' => true];
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

            // Cek data existing untuk tandai update/baru
            foreach ($results as &$row) {
                if ($row['user_id'] && $row['tanggal']) {
                    $existing = MappingShift::where('user_id', $row['user_id'])
                        ->where('tanggal', $row['tanggal'])
                        ->first();
                    $row['existing_id'] = $existing ? $existing->id : null;
                    $row['action'] = $existing ? 'update' : 'create';
                } else {
                    $row['existing_id'] = null;
                    $row['action'] = 'skip';
                }
            }

            // Statistik
            $stats = [
                'total'      => count($results),
                'valid'      => count(array_filter($results, fn($r) => $r['valid'] && in_array($r['match_type'], ['exact', 'employee_id'], true))),
                'fuzzy'      => count(array_filter($results, fn($r) => $r['valid'] && $r['match_type'] === 'fuzzy')),
                'invalid'    => count(array_filter($results, fn($r) => !$r['valid'])),
                'will_create'=> count(array_filter($results, fn($r) => $r['action'] === 'create')),
                'will_update'=> count(array_filter($results, fn($r) => $r['action'] === 'update')),
                'raw_rows'   => $rawPreview['total_rows'],
                'raw_columns'=> $rawPreview['total_columns'],
                'files'      => count($paths),
                'warnings'   => count($machineMeta['warnings']),
            ];

            // Simpan path di session untuk import nanti
            session([
                'smart_import_temp_path' => $paths[0],
                'smart_import_temp_paths' => $paths,
            ]);

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

        $importRows = $request->import_rows;
        $importDates = collect($importRows)->pluck('tanggal')->filter()->sort()->values();
        $importDateRange = $importDates->isNotEmpty()
            ? ['start' => $importDates->first(), 'end' => $importDates->last()]
            : null;
        $clockRows = collect($importRows)->filter(function (array $row) {
            return !empty($row['jam_absen']) || !empty($row['jam_pulang']);
        })->count();
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
                    if (($row['source_format'] ?? '') === 'machine_package_summary') {
                        $errors[] = "Baris " . ($index + 1) . ": File laporan rekap tidak bisa diimport sebagai data absensi. Upload Catatan Kehadiran Karyawan.";
                        $skipped++;
                        continue;
                    }

                    $targetTable = $row['target_table'] ?? 'mapping_shifts';
                    $shouldImportMapping = $targetTable === '' || str_contains($targetTable, 'mapping_shifts');
                    $shouldImportDinas = str_contains($targetTable, 'dinas_luars');

                    if ($shouldImportMapping) {
                        $existing = MappingShift::where('user_id', $row['user_id'])
                            ->where('tanggal', $row['tanggal'])
                            ->first();

                        $data = $this->buildMappingShiftData($row);

                        if ($existing) {
                            // Update existing record
                            $existing->update($this->mergeWithExistingAttendance($data, $existing, $row));
                            // Update performance points
                            KinerjaService::updateAttendancePoints($existing->id, $row['user_id']);
                            $updated++;
                        } else {
                            // Create new record
                            $data = $this->withDefaultSystemFields($data);

                            $newShift = MappingShift::create($data);
                            // Update performance points
                            KinerjaService::updateAttendancePoints($newShift->id, $row['user_id']);
                            $created++;
                        }
                    }

                    if ($shouldImportDinas) {
                        $dinas = $this->upsertDinasLuar($row);
                        if ($dinas->wasRecentlyCreated) {
                            $dinasCreated++;
                        } else {
                            $dinasUpdated++;
                        }
                    }
                } catch (\Exception $e) {
                    $errors[] = "Baris " . ($index + 1) . ": " . $e->getMessage();
                    $skipped++;
                }
            }

            DB::commit();

            // Hapus file temp
            $tempPaths = session('smart_import_temp_paths', []);
            $legacyTempPath = session('smart_import_temp_path');
            if ($legacyTempPath) {
                $tempPaths[] = $legacyTempPath;
            }

            foreach (array_unique(array_filter($tempPaths)) as $tempPath) {
                if (\Storage::disk('local')->exists($tempPath)) {
                    \Storage::disk('local')->delete($tempPath);
                }
            }
            session()->forget(['smart_import_temp_path', 'smart_import_temp_paths']);

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
                'status_absen' => 'Tidak Masuk',
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
                'target_table' => 'mapping_shifts',
                'conflict_notes' => [],
                'special_type' => null,
            ];
        }, $rawPreview['rows']);
    }

    private function buildMappingShiftData(array $row): array
    {
        $sourceFormat = $row['source_format'] ?? '';
        $keterangan = 'Smart Import Absensi';
        if ($sourceFormat === 'machine_package') {
            $keterangan = 'Smart Import Absensi (Scan Mesin)';
        } elseif ($sourceFormat === 'shift_schedule') {
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
