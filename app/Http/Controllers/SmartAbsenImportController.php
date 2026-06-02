<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Shift;
use App\Models\MappingShift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\SmartAbsenParser;
use App\Services\KinerjaService;

class SmartAbsenImportController extends Controller
{
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
        $request->validate([
            'file_absen' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'shift_id'   => 'required|exists:shifts,id',
        ]);

        try {
            $shift = Shift::findOrFail($request->shift_id);
            $parser = new SmartAbsenParser();

            // Simpan file sementara
            $path = $request->file('file_absen')->store('temp_import', 'local');
            $fullPath = storage_path('app/' . $path);

            // Parse Excel
            $rows = $parser->parseExcel($fullPath);

            if (count($rows) < 2) {
                return response()->json([
                    'success' => false,
                    'message' => 'File kosong atau hanya berisi header.'
                ], 422);
            }

            // Deteksi header row
            $headerRowIndex = $parser->findHeaderRow($rows);
            $headerRow = $rows[$headerRowIndex];

            // Deteksi kolom
            $columnMap = $parser->detectColumns($headerRow);
            $rawPreview = $parser->buildRawPreview($rows, $headerRowIndex);

            $users = User::orderBy('name', 'ASC')->get();

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
            ];

            // Simpan path di session untuk import nanti
            session(['smart_import_temp_path' => $path]);

            return response()->json([
                'success'     => true,
                'headers'     => $headerRow,
                'column_map'  => $columnMap,
                'raw_preview' => $rawPreview,
                'preview'     => $results,
                'stats'       => $stats,
                'shift_name'  => $shift->nama_shift . ' (' . $shift->jam_masuk . ' - ' . $shift->jam_keluar . ')',
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
        ]);

        $importRows = $request->import_rows;
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors  = [];

        DB::beginTransaction();
        try {
            foreach ($importRows as $index => $row) {
                try {
                    $existing = MappingShift::where('user_id', $row['user_id'])
                        ->where('tanggal', $row['tanggal'])
                        ->first();

                    $data = $this->buildMappingShiftData($row);

                    if ($existing) {
                        // Update existing record
                        $existing->update($this->mergeWithExistingAttendance($data, $existing));
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
                } catch (\Exception $e) {
                    $errors[] = "Baris " . ($index + 1) . ": " . $e->getMessage();
                    $skipped++;
                }
            }

            DB::commit();

            // Hapus file temp
            $tempPath = session('smart_import_temp_path');
            if ($tempPath && \Storage::disk('local')->exists($tempPath)) {
                \Storage::disk('local')->delete($tempPath);
            }
            session()->forget('smart_import_temp_path');

            return response()->json([
                'success' => true,
                'message' => 'Import berhasil!',
                'stats'   => [
                    'created' => $created,
                    'updated' => $updated,
                    'skipped' => $skipped,
                    'errors'  => $errors,
                ],
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
            ];
        }, $rawPreview['rows']);
    }

    private function buildMappingShiftData(array $row): array
    {
        return [
            'user_id'      => $row['user_id'],
            'shift_id'     => $row['shift_id'],
            'tanggal'      => $row['tanggal'],
            'jam_absen'    => !empty($row['jam_absen']) ? $row['jam_absen'] : null,
            'jam_pulang'   => !empty($row['jam_pulang']) ? $row['jam_pulang'] : null,
            'status_absen' => $row['status_absen'],
            'telat'        => $row['telat'] ?? 0,
            'pulang_cepat' => $row['pulang_cepat'] ?? 0,
            'keterangan_masuk' => 'Smart Import Absensi',
            'keterangan_pulang' => !empty($row['jam_pulang']) ? 'Smart Import Absensi' : null,
        ];
    }

    private function mergeWithExistingAttendance(array $data, MappingShift $existing): array
    {
        if ($data['jam_absen'] === null && $existing->jam_absen !== null) {
            unset($data['jam_absen'], $data['telat'], $data['keterangan_masuk']);
        }

        if ($data['jam_pulang'] === null && $existing->jam_pulang !== null) {
            unset($data['jam_pulang'], $data['pulang_cepat'], $data['keterangan_pulang']);
        }

        return $data;
    }

    private function withDefaultSystemFields(array $data): array
    {
        return array_merge($data, [
            'lock_location' => 0,
            'lat_absen' => 0,
            'long_absen' => 0,
            'lat_pulang' => 0,
            'long_pulang' => 0,
            'jarak_masuk' => 0,
            'jarak_pulang' => 0,
        ]);
    }
}
