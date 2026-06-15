<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SmartAbsenParser
{
    private array $normalizedKeys = [
        'preview_key',
        'row_index',
        'source_format',
        'raw_columns',
        'raw_nama',
        'raw_employee_id',
        'raw_tanggal',
        'raw_masuk',
        'raw_pulang',
        'raw_status',
        'tanggal',
        'jam_absen',
        'jam_pulang',
        'status_absen',
        'telat',
        'pulang_cepat',
        'shift_id',
        'user_id',
        'user_name',
        'confidence',
        'match_type',
        'valid',
        'errors',
        'source_sheet',
        'source_priority',
        'source_confidence',
        'target_table',
        'conflict_notes',
        'special_type',
    ];

    /**
     * Keyword mapping untuk deteksi kolom otomatis
     */
    private array $columnKeywords = [
        'nama'        => ['nama', 'name', 'karyawan', 'pegawai', 'employee', 'no name', 'nama pegawai', 'nama karyawan'],
        'employee_id' => ['employee id', 'emp id', 'pin', 'nik', 'nip', 'user id', 'user id.', 'id karyawan', 'kode karyawan', 'no pegawai'],
        'tanggal'     => ['tanggal', 'date', 'tgl', 'hari', 'tanggal absen'],
        'datetime'    => ['datetime', 'date time', 'tanggal jam', 'waktu scan', 'scan time', 'punch time', 'record time', 'clock time'],
        'jam_masuk'   => ['jam masuk', 'check in', 'checkin', 'masuk kerja', 'clock in', 'time in', 'jam datang'],
        'jam_pulang'  => ['jam pulang', 'check out', 'checkout', 'clock out', 'time out', 'jam keluar', 'jam pergi'],
        'jam'         => ['jam', 'time', 'waktu', 'scan', 'punch'],
        'status'      => ['status', 'keterangan', 'ket', 'info', 'hadir', 'kehadiran', 'state', 'verify state'],
        'hari_kehadiran' => ['hari kehadiran', 'standar/aktual', 'kehadiran standar', 'kehadiran aktual'],
        'tidak_hadir' => ['tidak hadir', 'absen hari', 'alpha hari'],
        'cuti'        => ['cuti', 'leave'],
    ];

    /**
     * Status mapping dari teks mesin ke status sistem
     */
    private array $statusMapping = [
        'masuk'        => ['masuk', 'hadir', 'present', 'p', 'h', 'datang', 'check in', 'checkin', 'attend'],
        'Tidak Masuk'  => ['tidak masuk', 'absen', 'absent', 'a', 'mangkir', 'alpha', 'tidak hadir'],
        'Sakit'        => ['sakit', 'sick', 's'],
        'Izin'         => ['izin', 'permission', 'i', 'permit', 'cuti', 'leave'],
    ];

    /**
     * Parse file Excel dan kembalikan array baris data mentah
     */
    public function parseExcel(string $filePath): array
    {
        return $this->worksheetRows($this->loadSpreadsheet($filePath)->getActiveSheet());
    }

    public function parseExcelSheets(string $filePath): array
    {
        $spreadsheet = $this->loadSpreadsheet($filePath);
        $sheets = [];

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $rows = $this->worksheetRows($sheet);
            if (count($rows) === 0) {
                continue;
            }

            $sheets[] = [
                'name' => $sheet->getTitle(),
                'rows' => $rows,
                'type' => $this->detectMachineFileType($rows),
            ];
        }

        return $sheets;
    }

    public function workbookFileTypes(string $filePath): array
    {
        return array_map(fn($sheet) => $sheet['type'], $this->parseExcelSheets($filePath));
    }

    private function loadSpreadsheet(string $filePath)
    {
        $previousReporting = error_reporting();
        set_error_handler(function ($severity, $message, $file) {
            if (str_contains((string) $file, 'PhpSpreadsheet')) {
                return true;
            }

            return false;
        });

        try {
            return IOFactory::load($filePath);
        } finally {
            restore_error_handler();
            error_reporting($previousReporting);
        }
    }

    private function worksheetRows($sheet): array
    {
        $rows = [];
        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();
        $highestColIndex = Coordinate::columnIndexFromString($highestCol);

        for ($row = 1; $row <= $highestRow; $row++) {
            $rowData = [];
            for ($colIndex = 1; $colIndex <= $highestColIndex; $colIndex++) {
                $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($colIndex) . $row);
                // Format tanggal/waktu yang mungkin tersimpan sebagai angka Excel
                if ($cell->getDataType() === DataType::TYPE_NUMERIC && ExcelDate::isDateTime($cell)) {
                    $rowData[] = ExcelDate::excelToDateTimeObject($cell->getValue())->format('Y-m-d H:i:s');
                } elseif ($cell->getDataType() === DataType::TYPE_NUMERIC) {
                    $rowData[] = trim(NumberFormat::toFormattedString(
                        $cell->getValue(),
                        $cell->getStyle()->getNumberFormat()->getFormatCode()
                    ));
                } else {
                    $rowData[] = trim((string) $cell->getValue());
                }
            }
            // Buang baris yang semua kolomnya kosong
            if (array_filter($rowData, fn($v) => $v !== '')) {
                $rows[] = $rowData;
            }
        }

        return $rows;
    }

    public function machineFileType(array $rows): string
    {
        return $this->detectMachineFileType($rows);
    }

    public function processMachineFiles(
        array $filePaths,
        Collection $users,
        int $shiftId,
        string $shiftJamMasuk,
        string $shiftJamKeluar
    ): array {
        $parsedFiles = [];
        $dateRange = [];
        $userDirectory = [];
        $dailyRows = [];
        $abnormalRows = [];
        $scheduleRows = [];
        $genericRows = [];
        $summaries = [];
        $rawPreviewRows = [];
        $rawHeaders = ['File', 'Sheet', 'Baris', 'Kolom', 'Nilai'];
        $warnings = [];

        foreach ($filePaths as $filePath) {
            $fileName = basename($filePath);
            $sheets = $this->parseExcelSheets($filePath);

            foreach ($sheets as $sheet) {
                $rows = $sheet['rows'];
                $sheetName = $sheet['name'];
                $type = $sheet['type'];
                $range = $this->detectReportDateRange($rows);
                if (!empty($range)) {
                    $dateRange = $this->mergeDateRange($dateRange, $range);
                }

                foreach ($this->buildMachineRawRows($rows, $fileName, $sheetName) as $rawRow) {
                    $rawPreviewRows[] = $rawRow;
                }

                if ($type === 'user_info') {
                    $userDirectory = array_replace($userDirectory, $this->parseMachineUserInfo($rows));
                } elseif ($type === 'shift_schedule') {
                    $scheduleRows = array_merge($scheduleRows, $this->parseMachineShiftScheduleRows($rows, $range, $fileName, $sheetName));
                } elseif ($type === 'personal_attendance') {
                    $dailyRows = array_merge($dailyRows, $this->parseMachinePersonalAttendanceRows($rows, $range, $fileName, $sheetName));
                    $dailyRows = array_merge($dailyRows, $this->parseMachineWidePersonalAttendanceRows($rows, $range, $fileName, $sheetName));
                } elseif ($type === 'abnormal_attendance') {
                    $abnormalRows = array_merge($abnormalRows, $this->parseMachineAbnormalRows($rows, $range, $fileName, $sheetName));
                } elseif ($type === 'attendance_summary') {
                    $summaries = array_replace($summaries, $this->parseMachineAttendanceSummaries($rows, $range, $fileName, $sheetName));
                } elseif ($type === 'generic') {
                    $genericRows = array_merge($genericRows, $this->parseGenericMachineRows($rows, $users, $shiftId, $shiftJamMasuk, $shiftJamKeluar, $fileName, $sheetName));
                }

                $parsedFiles[] = [
                    'file' => $fileName,
                    'sheet' => $sheetName,
                    'type' => $type,
                    'rows' => count($rows),
                ];
            }
        }

        $dates = !empty($dateRange) ? $this->dateRange($dateRange['start'], $dateRange['end']) : [];
        $employees = [];
        $records = [];
        $schedules = [];

        foreach ($dailyRows as $row) {
            $key = $this->machineEmployeeKey($row['employee_id'], $row['name']);
            $employees[$key] = $this->mergeMachineEmployee($employees[$key] ?? [], $row, $userDirectory);
            $records[$key][$row['tanggal']]['times'] = array_values(array_unique(array_merge(
                $records[$key][$row['tanggal']]['times'] ?? [],
                $row['times']
            )));
            $records[$key][$row['tanggal']]['raw'][] = $row;
        }

        foreach ($genericRows as $row) {
            $key = $this->machineEmployeeKey($row['employee_id'], $row['name']);
            $employees[$key] = $this->mergeMachineEmployee($employees[$key] ?? [], $row, $userDirectory);
            if (!empty($row['times'])) {
                $records[$key][$row['tanggal']]['times'] = array_values(array_unique(array_merge(
                    $records[$key][$row['tanggal']]['times'] ?? [],
                    $row['times']
                )));
                $records[$key][$row['tanggal']]['raw'][] = $row;
            }
            if (!empty($row['status'])) {
                $records[$key][$row['tanggal']]['generic'] = $row;
            }
        }

        foreach ($abnormalRows as $row) {
            $key = $this->machineEmployeeKey($row['employee_id'], $row['name']);
            $employees[$key] = $this->mergeMachineEmployee($employees[$key] ?? [], $row, $userDirectory);
            $records[$key][$row['tanggal']]['abnormal'] = $row;
            $records[$key][$row['tanggal']]['times'] = array_values(array_unique(array_merge(
                $records[$key][$row['tanggal']]['times'] ?? [],
                $row['times']
            )));
        }

        foreach ($scheduleRows as $row) {
            $key = $this->machineEmployeeKey($row['employee_id'], $row['name']);
            $employees[$key] = $this->mergeMachineEmployee($employees[$key] ?? [], $row, $userDirectory);
            $schedules[$key][$row['tanggal']] = $row;
        }

        foreach ($summaries as $key => $summary) {
            $employees[$key] = $this->mergeMachineEmployee($employees[$key] ?? [], $summary, $userDirectory);
        }

        if (empty($dailyRows) && empty($abnormalRows) && empty($scheduleRows) && empty($genericRows)) {
            $results = [];
            foreach ($employees as $key => $employee) {
                $name = $employee['name'] ?: ($userDirectory[$employee['employee_id']]['name'] ?? '');
                $employeeId = $employee['employee_id'];

                $results[] = $this->normalizeResultRow([
                    'preview_key' => 'machine-summary:' . $key,
                    'row_index' => $summaries[$key]['row_index'] ?? null,
                    'source_format' => 'machine_package_summary',
                    'raw_columns' => [
                        'User ID' => $employeeId,
                        'Nama' => $name,
                    ],
                    'raw_nama' => $name,
                    'raw_employee_id' => $employeeId,
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
                    'errors' => ['File laporan hanya berisi rekap dan tidak punya jam scan harian. Upload file Catatan Kehadiran Karyawan agar jam masuk/pulang terisi.'],
                    'source_sheet' => $summaries[$key]['sheet'] ?? '',
                    'source_priority' => 10,
                    'source_confidence' => 0,
                    'target_table' => 'mapping_shifts',
                    'conflict_notes' => [],
                    'special_type' => null,
                ]);
            }

            $warnings[] = 'File laporan rekap tidak dipakai untuk membuat data absensi final. Upload Catatan Kehadiran Karyawan sebagai sumber jam scan.';

            return [
                'results' => array_values($results),
                'raw_preview' => [
                    'header_row_index' => 0,
                    'data_start_index' => 0,
                    'headers' => $rawHeaders,
                    'rows' => $rawPreviewRows,
                    'total_rows' => count($rawPreviewRows),
                    'total_columns' => count($rawHeaders),
                ],
                'files' => $parsedFiles,
                'date_range' => $dateRange,
                'summaries' => array_values($summaries),
                'warnings' => array_values(array_unique($warnings)),
            ];
        }

        if (empty($dates)) {
            $dates = $this->datesFromMachineRecords($records);
        }
        $dates = $this->mergeMachineDates($dates, $this->datesFromMachineSchedules($schedules));

        $results = [];
        foreach ($employees as $key => $employee) {
            $name = $employee['name'] ?: ($userDirectory[$employee['employee_id']]['name'] ?? '');
            $employeeId = $employee['employee_id'];
            $matchResult = $this->matchEmployee($name, $users, $employeeId);
            $summary = $summaries[$key] ?? null;
            $employeeResults = [];

            foreach ($dates as $date) {
                $record = $records[$key][$date] ?? [];
                $times = $record['times'] ?? [];
                sort($times);

                $classification = $this->classifyMachineTimes($times, $date, $shiftJamMasuk, $shiftJamKeluar);
                $abnormal = $record['abnormal'] ?? null;
                $generic = $record['generic'] ?? null;
                $schedule = $schedules[$key][$date] ?? null;
                $status = $classification['status'];
                $sourceFormat = 'machine_package';
                $sourceSheet = $record['raw'][0]['sheet'] ?? ($abnormal['sheet'] ?? ($generic['sheet'] ?? ($schedule['sheet'] ?? '')));
                $sourcePriority = $classification['has_scan'] ? 100 : 20;
                $targetTable = 'mapping_shifts';
                $specialType = null;
                $conflictNotes = [];

                if ($schedule) {
                    $specialType = $schedule['special_type'] ?? null;
                    if (($schedule['target_table'] ?? '') === 'mapping_shifts,dinas_luars') {
                        $targetTable = 'mapping_shifts,dinas_luars';
                    }
                }

                if (!$classification['has_scan'] && $abnormal && $abnormal['status'] !== '') {
                    $status = $abnormal['status'];
                    $sourceSheet = $abnormal['sheet'] ?? $sourceSheet;
                    $sourcePriority = 80;
                }
                if (!$classification['has_scan'] && (!$abnormal || $abnormal['status'] === '') && $generic && $generic['status'] !== '') {
                    $status = $generic['status'];
                    $sourceSheet = $generic['sheet'] ?? $sourceSheet;
                    $sourcePriority = 70;
                }
                if (!$classification['has_scan'] && (!$abnormal || $abnormal['status'] === '') && (!$generic || $generic['status'] === '') && $schedule) {
                    $status = $schedule['status'];
                    $sourceFormat = 'shift_schedule';
                    $sourceSheet = $schedule['sheet'] ?? $sourceSheet;
                    $sourcePriority = 60;
                    $targetTable = $schedule['target_table'] ?? $targetTable;
                    $specialType = $schedule['special_type'] ?? $specialType;
                }
                if (!$classification['has_scan'] && !$abnormal && !$generic && !$schedule) {
                    $status = 'Libur';
                }

                $jamMasuk = $classification['jam_absen'];
                $jamPulang = $classification['jam_pulang'];
                $telat = $classification['telat'];
                $pulangCepat = $classification['pulang_cepat'];

                if ($abnormal) {
                    $telat = max($telat, (int) ($abnormal['telat'] ?? 0));
                    $pulangCepat = max($pulangCepat, (int) ($abnormal['pulang_cepat'] ?? 0));
                }

                if ($schedule && $classification['has_scan'] && !empty($schedule['status']) && !in_array($schedule['status'], ['Tidak Masuk', 'Libur'], true)) {
                    $conflictNotes[] = 'Ada scan pada tanggal berstatus ' . $schedule['status'] . ' di pengaturan shift.';
                }

                $errors = array_filter([
                    in_array($matchResult['match_type'], ['not_found', 'empty'], true)
                        ? 'Karyawan tidak ditemukan: "' . trim($employeeId . ' ' . $name) . '"'
                        : null,
                ]);

                $employeeResults[] = $this->normalizeResultRow([
                    'preview_key' => 'machine:' . $key . ':' . $date,
                    'row_index' => $abnormal['row_index'] ?? ($record['raw'][0]['row_index'] ?? null),
                    'source_format' => 'machine_package',
                    'raw_columns' => [
                        'User ID' => $employeeId,
                        'Nama' => $name,
                        'Tanggal' => $date,
                        'Times' => implode(', ', $times),
                        'Status Source' => $status,
                        'Source Sheet' => $sourceSheet,
                        'Target' => $targetTable,
                        'Summary' => $summary ? json_encode($summary, JSON_UNESCAPED_UNICODE) : '',
                    ],
                    'raw_nama' => $name,
                    'raw_employee_id' => $employeeId,
                    'raw_tanggal' => $date,
                    'raw_masuk' => $jamMasuk ?: '',
                    'raw_pulang' => $jamPulang ?: '',
                    'raw_status' => $status,
                    'tanggal' => $date,
                    'jam_absen' => $jamMasuk,
                    'jam_pulang' => $jamPulang,
                    'status_absen' => $status,
                    'telat' => $telat,
                    'pulang_cepat' => $pulangCepat,
                    'shift_id' => $shiftId,
                    'user_id' => $matchResult['user'] ? $matchResult['user']->id : null,
                    'user_name' => $matchResult['user'] ? $matchResult['user']->name : null,
                    'confidence' => $matchResult['confidence'],
                    'match_type' => $matchResult['match_type'],
                    'valid' => !in_array($matchResult['match_type'], ['not_found', 'empty'], true),
                    'errors' => $errors,
                    'source_sheet' => $sourceSheet,
                    'source_priority' => $sourcePriority,
                    'source_confidence' => $matchResult['confidence'],
                    'target_table' => $targetTable,
                    'conflict_notes' => $conflictNotes,
                    'special_type' => $specialType,
                ]);
            }

            if ($summary) {
                $employeeResults = $this->reconcileMachineRowsWithSummary($employeeResults, $summary);
            }

            $actual = $this->machineResultStatusCounts($employeeResults);
            if ($summary) {
                $summaryWarnings = $this->machineSummaryWarnings($name, $summary, $actual);
                $warnings = array_merge($warnings, $summaryWarnings);
            }

            $results = array_merge($results, $employeeResults);
        }

        return [
            'results' => array_values($results),
            'raw_preview' => [
                'header_row_index' => 0,
                'data_start_index' => 0,
                'headers' => $rawHeaders,
                'rows' => $rawPreviewRows,
                'total_rows' => count($rawPreviewRows),
                'total_columns' => count($rawHeaders),
            ],
            'files' => $parsedFiles,
            'date_range' => $dateRange,
            'summaries' => array_values($summaries),
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    /**
     * Deteksi kolom mana yang berisi nama/tanggal/jam/status berdasarkan header baris
     * Kembalikan array ['nama' => 0, 'tanggal' => 1, ...]
     */
    public function detectColumns(array $headerRow): array
    {
        $detected = [
            'nama'        => null,
            'employee_id' => null,
            'tanggal'     => null,
            'datetime'    => null,
            'jam_masuk'   => null,
            'jam_pulang'  => null,
            'jam'         => null,
            'status'      => null,
            'hari_kehadiran' => null,
            'tidak_hadir' => null,
            'cuti'        => null,
        ];

        foreach ($headerRow as $colIndex => $header) {
            $headerLower = $this->normalizeText($header);
            foreach ($this->columnKeywords as $key => $keywords) {
                if ($detected[$key] === null) {
                    foreach ($keywords as $keyword) {
                        if ($key === 'tanggal' && (
                            str_contains($headerLower, 'kehadiran')
                            || str_contains($headerLower, 'hadir')
                            || str_contains($headerLower, 'cuti')
                        )) {
                            continue;
                        }
                        if ($key === 'jam_masuk' && str_contains($headerLower, 'terlambat')) {
                            continue;
                        }
                        if ($key === 'jam_pulang' && str_contains($headerLower, 'lebih awal')) {
                            continue;
                        }
                        if ($key === 'jam' && (
                            str_contains($headerLower, 'jam kerja')
                            || str_contains($headerLower, 'lembur')
                            || str_contains($headerLower, 'terlambat')
                            || str_contains($headerLower, 'pulang lebih awal')
                        )) {
                            continue;
                        }
                        if (str_contains($headerLower, $keyword)) {
                            $detected[$key] = $colIndex;
                            break;
                        }
                    }
                }
            }
        }

        return $detected;
    }

    public function buildRawPreview(array $rows, int $headerRowIndex): array
    {
        $headers = $this->buildHeaderLabels($rows, $headerRowIndex);
        $dataStartIndex = $this->dataStartIndex($rows, $headerRowIndex);
        $rawRows = [];

        for ($i = $dataStartIndex; $i < count($rows); $i++) {
            $row = $rows[$i];
            if (!array_filter($row, fn($value) => trim((string) $value) !== '')) {
                continue;
            }

            $values = [];
            $columns = [];
            for ($col = 0; $col < count($headers); $col++) {
                $value = array_key_exists($col, $row) ? trim((string) $row[$col]) : '';
                $values[] = $value;
                $columns[$headers[$col]] = $value;
            }

            $rawRows[] = [
                'row_index' => $i,
                'row_number' => $i + 1,
                'values' => $values,
                'columns' => $columns,
            ];
        }

        return [
            'header_row_index' => $headerRowIndex,
            'data_start_index' => $dataStartIndex,
            'headers' => $headers,
            'rows' => $rawRows,
            'total_rows' => count($rawRows),
            'total_columns' => count($headers),
        ];
    }

    /**
     * Coba deteksi baris header secara otomatis
     * (biasanya baris pertama atau kedua yang mengandung keyword)
     */
    public function findHeaderRow(array $rows): int
    {
        foreach ($rows as $index => $row) {
            $score = 0;
            foreach ($row as $cell) {
                $cellLower = $this->normalizeText($cell);
                foreach ($this->columnKeywords as $keywords) {
                    foreach ($keywords as $keyword) {
                        if (str_contains($cellLower, $keyword)) {
                            $score++;
                            break 2;
                        }
                    }
                }
            }
            // Jika lebih dari 2 kolom cocok dengan keyword, ini kemungkinan header
            if ($score >= 2) {
                return $index;
            }
        }

        // Default: baris pertama
        return 0;
    }

    /**
     * Cocokkan nama dari file ke karyawan yang terdaftar di sistem
     * Menggunakan similar_text() untuk fuzzy matching
     * Kembalikan ['user' => User|null, 'confidence' => float, 'match_type' => string]
     */
    public function matchEmployee(string $rawName, Collection $users, ?string $rawEmployeeId = null): array
    {
        $rawNameClean = $this->normalizeText($rawName);
        $rawEmployeeIdClean = $this->normalizeText((string) $rawEmployeeId);

        if ($rawEmployeeIdClean !== '') {
            foreach ($users as $user) {
                $candidateIds = array_filter([
                    $user->employee_id ?? null,
                    $user->username ?? null,
                    $user->email ?? null,
                ]);

                foreach ($candidateIds as $candidateId) {
                    if ($this->normalizeText((string) $candidateId) === $rawEmployeeIdClean) {
                        return ['user' => $user, 'confidence' => 100, 'match_type' => 'employee_id'];
                    }
                }
            }
        }

        if (empty($rawNameClean)) {
            return ['user' => null, 'confidence' => 0, 'match_type' => 'empty'];
        }

        $bestMatch   = null;
        $bestScore   = 0;

        foreach ($users as $user) {
            $userName = $this->normalizeText($user->name);

            // Exact match (case-insensitive)
            if ($userName === $rawNameClean) {
                return ['user' => $user, 'confidence' => 100, 'match_type' => 'exact'];
            }

            // Contains match
            if (str_contains($userName, $rawNameClean) || str_contains($rawNameClean, $userName)) {
                $score = 90;
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestMatch = $user;
                }
                continue;
            }

            // Fuzzy match via similar_text
            similar_text($rawNameClean, $userName, $percent);
            if ($percent > $bestScore) {
                $bestScore = $percent;
                $bestMatch = $user;
            }
        }

        if ($bestScore >= 85) {
            return ['user' => $bestMatch, 'confidence' => round($bestScore, 1), 'match_type' => 'exact'];
        } elseif ($bestScore >= 60) {
            return ['user' => $bestMatch, 'confidence' => round($bestScore, 1), 'match_type' => 'fuzzy'];
        } else {
            return ['user' => null, 'confidence' => round($bestScore, 1), 'match_type' => 'not_found'];
        }
    }

    /**
     * Parse berbagai format tanggal ke Y-m-d
     * Support: dd/mm/yyyy, yyyy-mm-dd, dd-mm-yyyy, d/m/yyyy, dd MMM yyyy, dll
     */
    public function parseDate(string $raw): ?string
    {
        $raw = trim($raw);
        if (empty($raw)) return null;

        if (preg_match('/^(\d{4}-\d{1,2}-\d{1,2})\s+\d{1,2}[:.]\d{2}/', $raw, $m)) {
            return $this->parseDate($m[1]);
        }

        if (preg_match('/^(\d{1,2}\/\d{1,2}\/\d{4})\s+\d{1,2}[:.]\d{2}/', $raw, $m)) {
            return $this->parseDate($m[1]);
        }

        // Format dd/mm/yyyy atau d/m/yyyy
        if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $raw, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        // Format dd-mm-yyyy
        if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{4})$/', $raw, $m)) {
            return sprintf('%04d-%02d-%02d', $m[3], $m[2], $m[1]);
        }

        // Format yyyy-mm-dd (sudah benar)
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $raw, $m)) {
            return sprintf('%04d-%02d-%02d', $m[1], $m[2], $m[3]);
        }

        // Format dd-mm-yy
        if (preg_match('/^(\d{1,2})-(\d{1,2})-(\d{2})$/', $raw, $m)) {
            $year = (int)$m[3] < 50 ? 2000 + (int)$m[3] : 1900 + (int)$m[3];
            return sprintf('%04d-%02d-%02d', $year, $m[2], $m[1]);
        }

        // Format bulan nama Indonesia: "01 Mei 2025" atau "1 Jan 2025"
        $monthsId = [
            'jan' => '01', 'feb' => '02', 'mar' => '03', 'apr' => '04',
            'mei' => '05', 'jun' => '06', 'jul' => '07', 'agu' => '08',
            'sep' => '09', 'okt' => '10', 'nov' => '11', 'des' => '12',
            'january' => '01', 'february' => '02', 'march' => '03', 'april' => '04',
            'may' => '05', 'june' => '06', 'july' => '07', 'august' => '08',
            'september' => '09', 'october' => '10', 'november' => '11', 'december' => '12',
            'januari' => '01', 'februari' => '02', 'maret' => '03',
            'agustus' => '08', 'oktober' => '10', 'desember' => '12',
        ];

        if (preg_match('/^(\d{1,2})\s+([a-zA-Z]+)\s+(\d{4})$/', $raw, $m)) {
            $monthKey = strtolower(substr($m[2], 0, 3));
            if (isset($monthsId[$monthKey])) {
                return sprintf('%04d-%s-%02d', $m[3], $monthsId[$monthKey], $m[1]);
            }
            // Full name lookup
            $monthKeyFull = strtolower($m[2]);
            if (isset($monthsId[$monthKeyFull])) {
                return sprintf('%04d-%s-%02d', $m[3], $monthsId[$monthKeyFull], $m[1]);
            }
        }

        // Coba strtotime sebagai fallback
        $ts = strtotime($raw);
        if ($ts !== false && $ts > 0) {
            return date('Y-m-d', $ts);
        }

        return null;
    }

    /**
     * Parse berbagai format jam ke H:i
     * Support: 07:30, 07:30:00, 730, 7.30, 07.30 AM, dll
     */
    public function parseTime(string $raw): ?string
    {
        $raw = trim($raw);
        if (empty($raw) || in_array(strtolower($raw), ['-', 'n/a', 'na', '0', '00:00', '0:00', '0:00:00'])) {
            return null;
        }

        if (preg_match('/\b(\d{1,2}):(\d{2})(?::\d{2})?\s*(AM|PM)?\b/i', $raw, $m)) {
            $hour   = (int)$m[1];
            $minute = (int)$m[2];
            $ampm   = strtoupper($m[3] ?? '');
            if ($ampm === 'PM' && $hour < 12) $hour += 12;
            if ($ampm === 'AM' && $hour === 12) $hour = 0;
            return sprintf('%02d:%02d', $hour, $minute);
        }

        // Format HH:mm atau H:mm
        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?(?:\s*(AM|PM))?$/i', $raw, $m)) {
            $hour   = (int)$m[1];
            $minute = (int)$m[2];
            $ampm   = strtoupper($m[3] ?? '');
            if ($ampm === 'PM' && $hour < 12) $hour += 12;
            if ($ampm === 'AM' && $hour === 12) $hour = 0;
            return sprintf('%02d:%02d', $hour, $minute);
        }

        // Format HH.mm
        if (preg_match('/^(\d{1,2})\.(\d{2})$/', $raw, $m)) {
            return sprintf('%02d:%02d', (int)$m[1], (int)$m[2]);
        }

        // Pecahan hari Excel, misalnya 0.33125 = 07:57.
        if (preg_match('/^0[,.]\d+$/', $raw)) {
            $fraction = (float) str_replace(',', '.', $raw);
            $totalMinutes = (int) round($fraction * 24 * 60);
            $hour = intdiv($totalMinutes, 60) % 24;
            $minute = $totalMinutes % 60;

            return sprintf('%02d:%02d', $hour, $minute);
        }

        // Format 4 digit seperti 0730
        if (preg_match('/^(\d{3,4})$/', $raw, $m)) {
            $n = str_pad($raw, 4, '0', STR_PAD_LEFT);
            return sprintf('%02d:%02d', substr($n, 0, 2), substr($n, 2, 2));
        }

        return null;
    }

    /**
     * Parse teks status dari file ke status sistem
     */
    public function parseStatus(string $raw, ?string $jamMasuk): string
    {
        $rawLower = strtolower(trim($raw));

        foreach ($this->statusMapping as $systemStatus => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($rawLower, $keyword)) {
                    // 'masuk' dalam mapping adalah lowercase, return 'Masuk' (kapital)
                    return $systemStatus === 'masuk' ? 'Masuk' : $systemStatus;
                }
            }
        }

        // Auto-detect dari jam masuk jika tidak ada status
        if (!empty($jamMasuk)) {
            return 'Masuk';
        }

        return 'Tidak Masuk';
    }

    public function isCheckInStatus(string $raw): bool
    {
        $rawLower = $this->normalizeText($raw);
        return $rawLower !== '' && preg_match('/\b(in|masuk|datang|check in|checkin|clock in)\b/', $rawLower) === 1;
    }

    public function isCheckOutStatus(string $raw): bool
    {
        $rawLower = $this->normalizeText($raw);
        return $rawLower !== '' && preg_match('/\b(out|pulang|keluar|check out|checkout|clock out)\b/', $rawLower) === 1;
    }

    /**
     * Hitung keterlambatan dalam detik
     */
    public function hitungTelat(string $tanggal, string $shiftJamMasuk, ?string $jamAbsen): int
    {
        if (!$jamAbsen) return 0;

        $awal  = strtotime($tanggal . ' ' . $shiftJamMasuk);
        $akhir = strtotime($tanggal . ' ' . $jamAbsen);
        $diff  = $akhir - $awal;

        return $diff > 0 ? $diff : 0;
    }

    /**
     * Hitung pulang cepat dalam detik
     */
    public function hitungPulangCepat(string $tanggal, string $shiftJamKeluar, ?string $jamPulang): int
    {
        if (!$jamPulang) return 0;

        $akhir = strtotime($tanggal . ' ' . $shiftJamKeluar);
        $awal  = strtotime($tanggal . ' ' . $jamPulang);
        $diff  = $akhir - $awal;

        return $diff > 0 ? $diff : 0;
    }

    /**
     * Proses lengkap: dari baris mentah menjadi array preview yang siap ditampilkan
     */
    public function processRows(
        array $rows,
        int $headerRowIndex,
        array $columnMap,
        Collection $users,
        int $shiftId,
        string $shiftJamMasuk,
        string $shiftJamKeluar
    ): array {
        $reportDateRange = $this->detectReportDateRange($rows);
        if ($this->looksLikePersonalAttendanceBlocks($rows, $reportDateRange)) {
            return $this->processPersonalAttendanceBlocks($rows, $users, $shiftId, $shiftJamMasuk, $shiftJamKeluar, $reportDateRange);
        }

        if ($this->looksLikeAttendanceAnalysis($columnMap, $reportDateRange)) {
            return $this->processAttendanceAnalysisRows($rows, $headerRowIndex, $columnMap, $users, $shiftId, $reportDateRange);
        }

        if ($this->looksLikeEventLog($columnMap)) {
            return $this->processEventLogRows($rows, $headerRowIndex, $columnMap, $users, $shiftId, $shiftJamMasuk, $shiftJamKeluar);
        }

        $results = [];

        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];

            $rawNama    = isset($columnMap['nama'])       && isset($row[$columnMap['nama']])       ? $row[$columnMap['nama']]       : '';
            $rawEmployeeId = isset($columnMap['employee_id']) && isset($row[$columnMap['employee_id']]) ? $row[$columnMap['employee_id']] : '';
            $rawTanggal = isset($columnMap['tanggal'])    && isset($row[$columnMap['tanggal']])    ? $row[$columnMap['tanggal']]    : '';
            $rawMasuk   = isset($columnMap['jam_masuk'])  && isset($row[$columnMap['jam_masuk']])  ? $row[$columnMap['jam_masuk']]  : '';
            $rawPulang  = isset($columnMap['jam_pulang']) && isset($row[$columnMap['jam_pulang']]) ? $row[$columnMap['jam_pulang']] : '';
            $rawStatus  = isset($columnMap['status'])     && isset($row[$columnMap['status']])     ? $row[$columnMap['status']]     : '';

            // Skip baris yang nama dan tanggalnya kosong
            if (empty(trim($rawNama)) && empty(trim($rawEmployeeId)) && empty(trim($rawTanggal))) {
                continue;
            }

            $tanggal  = $this->parseDate($rawTanggal);
            $jamMasuk = $this->parseTime($rawMasuk);
            $jamPulang = $this->parseTime($rawPulang);
            $status   = $this->parseStatus($rawStatus, $jamMasuk);

            $matchResult = $this->matchEmployee($rawNama, $users, $rawEmployeeId);

            $telat       = 0;
            $pulangCepat = 0;

            if ($tanggal && $jamMasuk) {
                $telat = $this->hitungTelat($tanggal, $shiftJamMasuk, $jamMasuk);
            }

            if ($tanggal && $jamPulang) {
                $pulangCepat = $this->hitungPulangCepat($tanggal, $shiftJamKeluar, $jamPulang);
            }

            $results[] = $this->normalizeResultRow([
                'preview_key'   => (string) $i,
                'row_index'    => $i,
                'source_format' => 'daily',
                'raw_nama'     => $rawNama,
                'raw_employee_id' => $rawEmployeeId,
                'raw_tanggal'  => $rawTanggal,
                'raw_masuk'    => $rawMasuk,
                'raw_pulang'   => $rawPulang,
                'raw_status'   => $rawStatus,

                // Parsed
                'tanggal'      => $tanggal,
                'jam_absen'    => $jamMasuk,
                'jam_pulang'   => $jamPulang,
                'status_absen' => $status,
                'telat'        => $telat,
                'pulang_cepat' => $pulangCepat,
                'shift_id'     => $shiftId,

                // Match result
                'user_id'      => $matchResult['user'] ? $matchResult['user']->id : null,
                'user_name'    => $matchResult['user'] ? $matchResult['user']->name : null,
                'confidence'   => $matchResult['confidence'],
                'match_type'   => $matchResult['match_type'],

                // Validasi
                'valid'        => $matchResult['match_type'] !== 'not_found' && $tanggal !== null,
                'errors'       => array_filter([
                    $matchResult['match_type'] === 'not_found' ? 'Nama karyawan tidak ditemukan: "' . $rawNama . '"' : null,
                    $tanggal === null && !empty($rawTanggal)   ? 'Format tanggal tidak dikenali: "' . $rawTanggal . '"' : null,
                    $tanggal === null && empty($rawTanggal)    ? 'Tanggal kosong' : null,
                ]),
            ]);
        }

        return $results;
    }

    private function processEventLogRows(
        array $rows,
        int $headerRowIndex,
        array $columnMap,
        Collection $users,
        int $shiftId,
        string $shiftJamMasuk,
        string $shiftJamKeluar
    ): array {
        $groups = [];

        for ($i = $headerRowIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $rawNama = $this->valueAt($row, $columnMap['nama'] ?? null);
            $rawEmployeeId = $this->valueAt($row, $columnMap['employee_id'] ?? null);
            $rawTanggal = $this->valueAt($row, $columnMap['tanggal'] ?? null);
            $rawDateTime = $this->valueAt($row, $columnMap['datetime'] ?? null);
            $rawJam = $this->valueAt($row, $columnMap['jam'] ?? null);
            $rawStatus = $this->valueAt($row, $columnMap['status'] ?? null);

            if ($rawNama === '' && $rawEmployeeId === '' && $rawTanggal === '' && $rawDateTime === '' && $rawJam === '') {
                continue;
            }

            $tanggal = $rawDateTime !== '' ? $this->parseDate($rawDateTime) : $this->parseDate($rawTanggal);
            $jam = $rawDateTime !== '' ? $this->parseTime($rawDateTime) : $this->parseTime($rawJam);
            $matchResult = $this->matchEmployee($rawNama, $users, $rawEmployeeId);
            $groupKey = ($matchResult['user'] ? 'user:' . $matchResult['user']->id : 'raw:' . $this->normalizeText($rawEmployeeId . ' ' . $rawNama)) . '|' . ($tanggal ?: 'no-date');

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'row_index' => $i,
                    'raw_nama' => $rawNama,
                    'raw_employee_id' => $rawEmployeeId,
                    'raw_tanggal' => $rawTanggal ?: $rawDateTime,
                    'raw_status' => [],
                    'times' => [],
                    'in_times' => [],
                    'out_times' => [],
                    'tanggal' => $tanggal,
                    'match' => $matchResult,
                ];
            }

            if ($rawStatus !== '') {
                $groups[$groupKey]['raw_status'][] = $rawStatus;
            }

            if ($jam !== null) {
                $groups[$groupKey]['times'][] = $jam;
                if ($this->isCheckOutStatus($rawStatus)) {
                    $groups[$groupKey]['out_times'][] = $jam;
                } elseif ($this->isCheckInStatus($rawStatus)) {
                    $groups[$groupKey]['in_times'][] = $jam;
                }
            }
        }

        $results = [];
        foreach ($groups as $group) {
            sort($group['times']);
            sort($group['in_times']);
            sort($group['out_times']);

            $jamMasuk = $group['in_times'][0] ?? ($group['times'][0] ?? null);
            $jamPulang = $group['out_times'] ? end($group['out_times']) : ($group['times'] ? end($group['times']) : null);
            if ($jamPulang === $jamMasuk) {
                $jamPulang = null;
            }

            $tanggal = $group['tanggal'];
            $matchResult = $group['match'];
            $telat = ($tanggal && $jamMasuk) ? $this->hitungTelat($tanggal, $shiftJamMasuk, $jamMasuk) : 0;
            $pulangCepat = ($tanggal && $jamPulang) ? $this->hitungPulangCepat($tanggal, $shiftJamKeluar, $jamPulang) : 0;

            $results[] = $this->normalizeResultRow([
                'preview_key'   => (string) $group['row_index'],
                'row_index'    => $group['row_index'],
                'source_format' => 'event_log',
                'raw_nama'     => $group['raw_nama'],
                'raw_employee_id' => $group['raw_employee_id'],
                'raw_tanggal'  => $group['raw_tanggal'],
                'raw_masuk'    => $jamMasuk ?: '',
                'raw_pulang'   => $jamPulang ?: '',
                'raw_status'   => implode(', ', array_unique($group['raw_status'])),
                'tanggal'      => $tanggal,
                'jam_absen'    => $jamMasuk,
                'jam_pulang'   => $jamPulang,
                'status_absen' => $this->parseStatus(implode(' ', $group['raw_status']), $jamMasuk),
                'telat'        => $telat,
                'pulang_cepat' => $pulangCepat,
                'shift_id'     => $shiftId,
                'user_id'      => $matchResult['user'] ? $matchResult['user']->id : null,
                'user_name'    => $matchResult['user'] ? $matchResult['user']->name : null,
                'confidence'   => $matchResult['confidence'],
                'match_type'   => $matchResult['match_type'],
                'valid'        => !in_array($matchResult['match_type'], ['not_found', 'empty'], true) && $tanggal !== null && $jamMasuk !== null,
                'errors'       => array_filter([
                    in_array($matchResult['match_type'], ['not_found', 'empty'], true) ? 'Karyawan tidak ditemukan: "' . trim($group['raw_employee_id'] . ' ' . $group['raw_nama']) . '"' : null,
                    $tanggal === null ? 'Tanggal tidak dikenali' : null,
                    $jamMasuk === null ? 'Jam scan tidak dikenali' : null,
                ]),
            ]);
        }

        return array_values($results);
    }

    private function processPersonalAttendanceBlocks(
        array $rows,
        Collection $users,
        int $shiftId,
        string $shiftJamMasuk,
        string $shiftJamKeluar,
        array $reportDateRange
    ): array {
        $blocks = $this->detectPersonalAttendanceBlocks($rows);
        $dates = $this->dateRange($reportDateRange['start'], $reportDateRange['end']);
        $results = [];

        foreach ($blocks as $index => $block) {
            $nextStart = $blocks[$index + 1]['start_col'] ?? $this->maxColumnCount($rows);
            $endCol = max($block['start_col'], $nextStart - 1);
            $name = $this->valueNearLabel($rows[$block['meta_row']], $block['start_col'], $endCol, 'nama');
            $employeeId = $this->valueNearLabel($rows[$block['meta_row'] + 1] ?? [], $block['start_col'], $endCol, 'user id');
            $matchResult = $this->matchEmployee($name, $users, $employeeId);
            $detailStart = $this->findDetailStartRow($rows, $block['meta_row'], $block['start_col'], $endCol);
            $timeColumns = $detailStart !== null
                ? $this->detectBlockTimeColumns($rows, $detailStart, $block['start_col'], $endCol)
                : ['in' => [], 'out' => []];
            $dayRows = $detailStart !== null
                ? $this->collectBlockDayRows($rows, $detailStart, $block['start_col'], $endCol)
                : [];

            foreach ($dates as $date) {
                $day = (int) substr($date, 8, 2);
                $rowIndex = $dayRows[$day] ?? null;
                $row = $rowIndex !== null ? ($rows[$rowIndex] ?? []) : [];
                $jamMasuk = $this->firstParsedTime($row, $timeColumns['in']);
                $jamPulang = $this->lastParsedTime($row, $timeColumns['out']);
                if ($jamPulang === $jamMasuk) {
                    $jamPulang = null;
                }

                $telat = ($jamMasuk !== null) ? $this->hitungTelat($date, $shiftJamMasuk, $jamMasuk) : 0;
                $pulangCepat = ($jamPulang !== null) ? $this->hitungPulangCepat($date, $shiftJamKeluar, $jamPulang) : 0;
                $rawColumns = $this->blockRawColumns($rows, $block, $endCol, $rowIndex, $timeColumns);

                $results[] = $this->normalizeResultRow([
                    'preview_key' => $block['start_col'] . ':' . $date,
                    'row_index' => $rowIndex ?? $block['meta_row'],
                    'source_format' => 'personal_attendance_blocks',
                    'raw_columns' => $rawColumns,
                    'raw_nama' => $name,
                    'raw_employee_id' => $employeeId,
                    'raw_tanggal' => $date,
                    'raw_masuk' => $jamMasuk ?: '',
                    'raw_pulang' => $jamPulang ?: '',
                    'raw_status' => $jamMasuk ? 'Ada scan masuk' : 'Tidak ada scan',
                    'tanggal' => $date,
                    'jam_absen' => $jamMasuk,
                    'jam_pulang' => $jamPulang,
                    'status_absen' => $jamMasuk ? 'Masuk' : 'Tidak Masuk',
                    'telat' => $telat,
                    'pulang_cepat' => $pulangCepat,
                    'shift_id' => $shiftId,
                    'user_id' => $matchResult['user'] ? $matchResult['user']->id : null,
                    'user_name' => $matchResult['user'] ? $matchResult['user']->name : null,
                    'confidence' => $matchResult['confidence'],
                    'match_type' => $matchResult['match_type'],
                    'valid' => !in_array($matchResult['match_type'], ['not_found', 'empty'], true),
                    'errors' => array_filter([
                        in_array($matchResult['match_type'], ['not_found', 'empty'], true)
                            ? 'Karyawan tidak ditemukan: "' . trim($employeeId . ' ' . $name) . '"'
                            : null,
                    ]),
                ]);
            }
        }

        return $results;
    }

    private function processAttendanceAnalysisRows(
        array $rows,
        int $headerRowIndex,
        array $columnMap,
        Collection $users,
        int $shiftId,
        array $reportDateRange
    ): array {
        $dates = $this->dateRange($reportDateRange['start'], $reportDateRange['end']);
        $dateCount = count($dates);
        $results = [];
        $dataStartIndex = $this->dataStartIndex($rows, $headerRowIndex);
        $rawPreview = $this->buildRawPreview($rows, $headerRowIndex);
        $rawRowsByIndex = collect($rawPreview['rows'])->keyBy('row_index');

        for ($i = $dataStartIndex; $i < count($rows); $i++) {
            $row = $rows[$i];
            $rawNama = $this->valueAt($row, $columnMap['nama'] ?? null);
            $rawEmployeeId = $this->valueAt($row, $columnMap['employee_id'] ?? null);

            if ($rawNama === '' && $rawEmployeeId === '') {
                continue;
            }

            $rawAttendance = $this->valueAt($row, $columnMap['hari_kehadiran'] ?? null);
            $rawAbsent = $this->valueAt($row, $columnMap['tidak_hadir'] ?? null);
            $rawCuti = $this->valueAt($row, $columnMap['cuti'] ?? null);
            $attendance = $this->parseAttendancePair($rawAttendance);
            $actualDays = $attendance['actual'];
            $absentDays = $this->parseNumber($rawAbsent);
            $cutiDays = $this->parseNumber($rawCuti);
            $matchResult = $this->matchEmployee($rawNama, $users, $rawEmployeeId);
            $rawColumns = $rawRowsByIndex->get($i)['columns'] ?? [];

            $canExpandAsAbsent = $absentDays >= $dateCount && $actualDays == 0.0;
            $canExpandAsCuti = $cutiDays >= $dateCount && $actualDays == 0.0 && !$canExpandAsAbsent;
            $baseErrors = array_filter([
                in_array($matchResult['match_type'], ['not_found', 'empty'], true) ? 'Karyawan tidak ditemukan: "' . trim($rawEmployeeId . ' ' . $rawNama) . '"' : null,
                !$canExpandAsAbsent && !$canExpandAsCuti ? 'File ini berupa rekap rentang tanggal. Baris ini hanya bisa diimport otomatis jika seluruh rentang berstatus Tidak Masuk atau Cuti.' : null,
            ]);

            $status = $canExpandAsCuti ? 'Cuti' : 'Tidak Masuk';
            foreach ($dates as $date) {
                $results[] = $this->normalizeResultRow([
                    'preview_key'   => $i . ':' . $date,
                    'row_index'     => $i,
                    'source_format' => 'attendance_analysis',
                    'raw_columns'   => $rawColumns,
                    'raw_nama'      => $rawNama,
                    'raw_employee_id' => $rawEmployeeId,
                    'raw_tanggal'   => $reportDateRange['start'] . ' - ' . $reportDateRange['end'],
                    'raw_masuk'     => '',
                    'raw_pulang'    => '',
                    'raw_status'    => trim('Hari Kehadiran: ' . $rawAttendance . '; Tidak hadir: ' . $rawAbsent . '; Cuti: ' . $rawCuti),
                    'tanggal'       => $date,
                    'jam_absen'     => null,
                    'jam_pulang'    => null,
                    'status_absen'  => $status,
                    'telat'         => 0,
                    'pulang_cepat'  => 0,
                    'shift_id'      => $shiftId,
                    'user_id'       => $matchResult['user'] ? $matchResult['user']->id : null,
                    'user_name'     => $matchResult['user'] ? $matchResult['user']->name : null,
                    'confidence'    => $matchResult['confidence'],
                    'match_type'    => $matchResult['match_type'],
                    'valid'         => !in_array($matchResult['match_type'], ['not_found', 'empty'], true) && empty($baseErrors),
                    'errors'        => $baseErrors,
                ]);
            }
        }

        return $results;
    }

    private function looksLikeEventLog(array $columnMap): bool
    {
        if ($this->hasAttendanceAnalysisColumns($columnMap)) {
            return false;
        }

        $hasSingleTime = ($columnMap['datetime'] ?? null) !== null || (
            ($columnMap['tanggal'] ?? null) !== null
            && ($columnMap['jam'] ?? null) !== null
            && ($columnMap['jam_masuk'] ?? null) === null
            && ($columnMap['jam_pulang'] ?? null) === null
        );

        return $hasSingleTime && (($columnMap['nama'] ?? null) !== null || ($columnMap['employee_id'] ?? null) !== null);
    }

    private function looksLikePersonalAttendanceBlocks(array $rows, array $reportDateRange): bool
    {
        if (empty($reportDateRange)) {
            return false;
        }

        $text = $this->normalizeText(implode(' ', array_map(fn($row) => implode(' ', $row), array_slice($rows, 0, 12))));

        return str_contains($text, 'catatan kehadiran karyawan')
            || (str_contains($text, 'catatan kehadiran') && count($this->detectPersonalAttendanceBlocks($rows)) > 0);
    }

    private function detectPersonalAttendanceBlocks(array $rows): array
    {
        $blocks = [];

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                if ($this->normalizeText((string) $value) !== 'dept') {
                    continue;
                }

                if ($this->findLabelColumn($row, $colIndex, min($colIndex + 10, count($row) - 1), 'nama') === null) {
                    continue;
                }

                $nextRow = $rows[$rowIndex + 1] ?? [];
                if ($this->findLabelColumn($nextRow, $colIndex, min($colIndex + 10, count($nextRow) - 1), 'tanggal') === null) {
                    continue;
                }

                $blocks[] = [
                    'meta_row' => $rowIndex,
                    'start_col' => $colIndex,
                ];
            }

            if (!empty($blocks)) {
                break;
            }
        }

        return array_values($blocks);
    }

    private function valueNearLabel(array $row, int $startCol, int $endCol, string $label): string
    {
        $labelCol = $this->findLabelColumn($row, $startCol, $endCol, $label);
        if ($labelCol === null) {
            return '';
        }

        for ($col = $labelCol + 1; $col <= $endCol; $col++) {
            $value = trim((string) ($row[$col] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function findLabelColumn(array $row, int $startCol, int $endCol, string $label): ?int
    {
        $label = $this->normalizeText($label);
        for ($col = $startCol; $col <= $endCol; $col++) {
            if ($this->normalizeText((string) ($row[$col] ?? '')) === $label) {
                return $col;
            }
        }

        return null;
    }

    private function findDetailStartRow(array $rows, int $metaRow, int $startCol, int $endCol): ?int
    {
        for ($rowIndex = $metaRow + 1; $rowIndex < count($rows); $rowIndex++) {
            for ($col = $startCol; $col <= $endCol; $col++) {
                if ($this->normalizeText((string) ($rows[$rowIndex][$col] ?? '')) === 'catatan kehadiran') {
                    return $rowIndex;
                }
            }
        }

        return null;
    }

    private function detectBlockTimeColumns(array $rows, int $detailStart, int $startCol, int $endCol): array
    {
        $in = [];
        $out = [];

        for ($rowIndex = $detailStart + 1; $rowIndex <= min($detailStart + 4, count($rows) - 1); $rowIndex++) {
            $row = $rows[$rowIndex] ?? [];
            for ($col = $startCol; $col <= $endCol; $col++) {
                $value = $this->normalizeText((string) ($row[$col] ?? ''));
                if ($value === 'jam masuk') {
                    $in[] = $col;
                } elseif ($value === 'jam keluar') {
                    $out[] = $col;
                }
            }
        }

        return [
            'in' => array_values(array_unique($in)),
            'out' => array_values(array_unique($out)),
        ];
    }

    private function collectBlockDayRows(array $rows, int $detailStart, int $startCol, int $endCol): array
    {
        $dayRows = [];

        for ($rowIndex = $detailStart + 1; $rowIndex < count($rows); $rowIndex++) {
            $dateLabel = trim((string) ($rows[$rowIndex][$startCol] ?? ''));

            if (preg_match('/^(\d{1,2})\b/', $dateLabel, $matches)) {
                $dayRows[(int) $matches[1]] = $rowIndex;
                continue;
            }

            if (!empty($dayRows) && !$this->rowHasValueInRange($rows[$rowIndex] ?? [], $startCol, $endCol)) {
                break;
            }
        }

        return $dayRows;
    }

    private function firstParsedTime(array $row, array $columns): ?string
    {
        foreach ($columns as $col) {
            $time = $this->parseTime((string) ($row[$col] ?? ''));
            if ($time !== null) {
                return $time;
            }
        }

        return null;
    }

    private function lastParsedTime(array $row, array $columns): ?string
    {
        $times = [];
        foreach ($columns as $col) {
            $time = $this->parseTime((string) ($row[$col] ?? ''));
            if ($time !== null) {
                $times[] = $time;
            }
        }

        sort($times);

        return $times ? end($times) : null;
    }

    private function blockRawColumns(array $rows, array $block, int $endCol, ?int $dayRowIndex, array $timeColumns): array
    {
        $metaRow = $rows[$block['meta_row']] ?? [];
        $dateRow = $rows[$block['meta_row'] + 1] ?? [];
        $dayRow = $dayRowIndex !== null ? ($rows[$dayRowIndex] ?? []) : [];
        $raw = [
            'Dept' => $this->valueNearLabel($metaRow, $block['start_col'], $endCol, 'dept'),
            'Nama' => $this->valueNearLabel($metaRow, $block['start_col'], $endCol, 'nama'),
            'Tanggal' => $this->valueNearLabel($dateRow, $block['start_col'], $endCol, 'tanggal'),
            'User ID' => $this->valueNearLabel($dateRow, $block['start_col'], $endCol, 'user id'),
            'Tanggal/Minggu' => trim((string) ($dayRow[$block['start_col']] ?? '')),
        ];

        foreach ($timeColumns['in'] as $index => $col) {
            $raw['Jam Masuk ' . ($index + 1)] = trim((string) ($dayRow[$col] ?? ''));
        }
        foreach ($timeColumns['out'] as $index => $col) {
            $raw['Jam Keluar ' . ($index + 1)] = trim((string) ($dayRow[$col] ?? ''));
        }

        return $raw;
    }

    private function maxColumnCount(array $rows): int
    {
        return max(array_map('count', $rows)) ?: 0;
    }

    private function rowHasValueInRange(array $row, int $startCol, int $endCol): bool
    {
        for ($col = $startCol; $col <= $endCol; $col++) {
            if (trim((string) ($row[$col] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    private function looksLikeAttendanceAnalysis(array $columnMap, array $reportDateRange): bool
    {
        return !empty($reportDateRange)
            && (($columnMap['nama'] ?? null) !== null || ($columnMap['employee_id'] ?? null) !== null)
            && $this->hasAttendanceAnalysisColumns($columnMap);
    }

    private function hasAttendanceAnalysisColumns(array $columnMap): bool
    {
        return ($columnMap['hari_kehadiran'] ?? null) !== null
            || ($columnMap['tidak_hadir'] ?? null) !== null
            || ($columnMap['cuti'] ?? null) !== null;
    }

    private function valueAt(array $row, ?int $index): string
    {
        return $index !== null && array_key_exists($index, $row) ? trim((string) $row[$index]) : '';
    }

    private function detectReportDateRange(array $rows): array
    {
        foreach ($rows as $row) {
            foreach ($row as $cell) {
                $value = trim((string) $cell);
                if ($value === '') {
                    continue;
                }

                if (preg_match('/(\d{4}-\d{1,2}-\d{1,2})\s*[~\-]\s*(\d{4}-\d{1,2}-\d{1,2})/', $value, $matches)) {
                    $start = $this->parseDate($matches[1]);
                    $end = $this->parseDate($matches[2]);

                    if ($start && $end) {
                        return ['start' => $start, 'end' => $end];
                    }
                }
            }
        }

        return [];
    }

    private function dateRange(string $start, string $end): array
    {
        $startDate = new \DateTimeImmutable($start);
        $endDate = new \DateTimeImmutable($end);
        if ($endDate < $startDate) {
            return [];
        }

        $dates = [];
        for ($date = $startDate; $date <= $endDate; $date = $date->modify('+1 day')) {
            $dates[] = $date->format('Y-m-d');
        }

        return $dates;
    }

    private function mergeDateRange(array $current, array $incoming): array
    {
        if (empty($incoming)) {
            return $current;
        }

        if (empty($current)) {
            return $incoming;
        }

        return [
            'start' => min($current['start'], $incoming['start']),
            'end' => max($current['end'], $incoming['end']),
        ];
    }

    private function mergeMachineDates(array $first, array $second): array
    {
        $dates = array_values(array_unique(array_merge($first, $second)));
        sort($dates);

        return $dates;
    }

    private function datesFromMachineSchedules(array $schedules): array
    {
        $dates = [];
        foreach ($schedules as $employeeSchedules) {
            foreach (array_keys($employeeSchedules) as $date) {
                $dates[$date] = true;
            }
        }

        $dates = array_keys($dates);
        sort($dates);

        return $dates;
    }

    private function parseAttendancePair(string $raw): array
    {
        if (preg_match('/(\d+(?:[,.]\d+)?)\s*\/\s*(\d+(?:[,.]\d+)?)/', $raw, $matches)) {
            return [
                'standard' => (float) str_replace(',', '.', $matches[1]),
                'actual' => (float) str_replace(',', '.', $matches[2]),
            ];
        }

        return ['standard' => 0.0, 'actual' => 0.0];
    }

    private function parseNumber(string $raw): float
    {
        if (preg_match('/-?\d+(?:[,.]\d+)?/', $raw, $matches)) {
            return (float) str_replace(',', '.', $matches[0]);
        }

        return 0.0;
    }

    private function buildHeaderLabels(array $rows, int $headerRowIndex): array
    {
        $headerRow = $rows[$headerRowIndex] ?? [];
        $subHeaderRow = $this->looksLikeSubHeaderRow($rows, $headerRowIndex) ? ($rows[$headerRowIndex + 1] ?? []) : [];
        $columnCount = max(count($headerRow), count($subHeaderRow));
        $headers = [];
        $used = [];

        for ($i = 0; $i < $columnCount; $i++) {
            $parts = array_values(array_filter([
                trim((string) ($headerRow[$i] ?? '')),
                trim((string) ($subHeaderRow[$i] ?? '')),
            ], fn($value) => $value !== ''));

            $label = implode(' - ', array_unique($parts));
            if ($label === '') {
                $label = 'Kolom ' . $this->excelColumnName($i);
            }

            $baseLabel = $label;
            $suffix = 2;
            while (isset($used[$label])) {
                $label = $baseLabel . ' (' . $suffix . ')';
                $suffix++;
            }
            $used[$label] = true;
            $headers[] = $label;
        }

        return $headers;
    }

    private function dataStartIndex(array $rows, int $headerRowIndex): int
    {
        return $headerRowIndex + ($this->looksLikeSubHeaderRow($rows, $headerRowIndex) ? 2 : 1);
    }

    private function looksLikeSubHeaderRow(array $rows, int $headerRowIndex): bool
    {
        $nextRow = $rows[$headerRowIndex + 1] ?? null;
        if (!$nextRow) {
            return false;
        }

        $text = $this->normalizeText(implode(' ', $nextRow));
        $score = 0;
        foreach (['standar', 'aktual', 'kali', 'menit', 'normal', 'khusus', 'catatan', 'kerja lembur', 'tunjangan'] as $keyword) {
            if (str_contains($text, $keyword)) {
                $score++;
            }
        }

        return $score >= 2;
    }

    private function excelColumnName(int $index): string
    {
        $name = '';
        $index++;
        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $name = chr(65 + $mod) . $name;
            $index = intdiv($index - $mod, 26);
        }

        return $name;
    }

    private function detectMachineFileType(array $rows): string
    {
        $text = $this->normalizeText(implode(' ', array_map(fn($row) => implode(' ', $row), array_slice($rows, 0, 8))));

        if (str_contains($text, 'pengaturan shift kehadiran')) {
            return 'shift_schedule';
        }

        if (str_contains($text, 'catatan kehadiran karyawan')) {
            return 'personal_attendance';
        }

        if (str_contains($text, 'kehadiran tidak normal')) {
            return 'abnormal_attendance';
        }

        if (str_contains($text, 'analisa kehadiran')) {
            return 'attendance_summary';
        }

        if (str_contains($text, 'informasi pengguna')) {
            return 'user_info';
        }

        return 'generic';
    }

    private function buildMachineRawRows(array $rows, string $fileName, string $sheetName = ''): array
    {
        $rawRows = [];
        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $colIndex => $value) {
                $value = trim((string) $value);
                if ($value === '') {
                    continue;
                }

                $rawRows[] = [
                    'row_index' => ($rowIndex * 1000) + $colIndex,
                    'row_number' => $rowIndex + 1,
                    'values' => [$fileName, $sheetName, $rowIndex + 1, $this->excelColumnName($colIndex), $value],
                    'columns' => [
                        'File' => $fileName,
                        'Sheet' => $sheetName,
                        'Baris' => $rowIndex + 1,
                        'Kolom' => $this->excelColumnName($colIndex),
                        'Nilai' => $value,
                    ],
                ];
            }
        }

        return $rawRows;
    }

    private function parseMachineUserInfo(array $rows): array
    {
        $headerIndex = null;
        foreach ($rows as $index => $row) {
            $text = $this->normalizeText(implode(' ', $row));
            if (str_contains($text, 'user id') && str_contains($text, 'nama')) {
                $headerIndex = $index;
                break;
            }
        }

        if ($headerIndex === null) {
            return [];
        }

        $map = [];
        for ($i = $headerIndex + 1; $i < count($rows); $i++) {
            $employeeId = $this->cleanMachineLabelValue($this->valueAt($rows[$i], 0));
            $name = $this->cleanMachineLabelValue($this->valueAt($rows[$i], 1));
            $department = $this->cleanMachineLabelValue($this->valueAt($rows[$i], 2));

            if ($employeeId === '' || $name === '') {
                continue;
            }

            $map[$employeeId] = [
                'employee_id' => $employeeId,
                'name' => $name,
                'department' => $department,
            ];
        }

        return $map;
    }

    private function parseMachineShiftScheduleRows(array $rows, array $reportDateRange, string $fileName, string $sheetName = ''): array
    {
        if (empty($reportDateRange)) {
            return [];
        }

        $headerIndex = null;
        $dayColumns = [];
        foreach ($rows as $index => $row) {
            $text = $this->normalizeText(implode(' ', $row));
            if (!str_contains($text, 'user id') || !str_contains($text, 'nama')) {
                continue;
            }

            foreach ($row as $colIndex => $value) {
                $value = trim((string) $value);
                if (preg_match('/^\d{1,2}$/', $value)) {
                    $dayColumns[(int) $value] = $colIndex;
                }
            }

            if (!empty($dayColumns)) {
                $headerIndex = $index;
                break;
            }
        }

        if ($headerIndex === null || empty($dayColumns)) {
            return [];
        }

        $headers = $this->buildHeaderLabels($rows, $headerIndex);
        $employeeIdCol = $this->findHeaderColumn($headers, ['user id', 'employee id', 'pin']) ?? 0;
        $nameCol = $this->findHeaderColumn($headers, ['nama', 'name']) ?? 1;
        $departmentCol = $this->findHeaderColumn($headers, ['departemen', 'department', 'dept']) ?? 2;
        $specialCodes = $this->parseShiftScheduleSpecialCodes($rows);
        $dates = $this->dateRange($reportDateRange['start'], $reportDateRange['end']);
        $dateByDay = [];
        foreach ($dates as $date) {
            $dateByDay[(int) substr($date, 8, 2)] = $date;
        }

        $dataStart = $headerIndex + 1;
        if ($this->looksLikeWeekdayRow($rows[$dataStart] ?? [], $dayColumns)) {
            $dataStart++;
        }

        $results = [];
        for ($i = $dataStart; $i < count($rows); $i++) {
            $row = $rows[$i];
            $employeeId = $this->cleanMachineLabelValue($this->valueAt($row, $employeeIdCol));
            $name = $this->cleanMachineLabelValue($this->valueAt($row, $nameCol));
            if ($employeeId === '' && $name === '') {
                continue;
            }

            foreach ($dateByDay as $day => $date) {
                if (!isset($dayColumns[$day])) {
                    continue;
                }

                $rawValue = $this->cleanMachineLabelValue($this->valueAt($row, $dayColumns[$day]));
                $status = $this->scheduleStatusFromValue($rawValue, $specialCodes);
                $results[] = [
                    'file' => $fileName,
                    'sheet' => $sheetName,
                    'row_index' => $i,
                    'employee_id' => $employeeId,
                    'name' => $name,
                    'department' => $this->cleanMachineLabelValue($this->valueAt($row, $departmentCol)),
                    'tanggal' => $date,
                    'status' => $status['status'],
                    'raw_status' => $rawValue,
                    'target_table' => $status['target_table'],
                    'special_type' => $status['special_type'],
                ];
            }
        }

        return $results;
    }

    private function parseMachineWidePersonalAttendanceRows(array $rows, array $reportDateRange, string $fileName, string $sheetName = ''): array
    {
        if (empty($reportDateRange)) {
            return [];
        }

        $blocks = $this->detectPersonalAttendanceBlocks($rows);
        if (empty($blocks)) {
            return [];
        }

        $dates = $this->dateRange($reportDateRange['start'], $reportDateRange['end']);
        $results = [];

        foreach ($blocks as $index => $block) {
            $nextStart = $blocks[$index + 1]['start_col'] ?? $this->maxColumnCount($rows);
            $endCol = max($block['start_col'], $nextStart - 1);
            $detailStart = $this->findDetailStartRow($rows, $block['meta_row'], $block['start_col'], $endCol);
            if ($detailStart === null) {
                continue;
            }

            $timeColumns = $this->detectBlockTimeColumns($rows, $detailStart, $block['start_col'], $endCol);
            $dayRows = $this->collectBlockDayRows($rows, $detailStart, $block['start_col'], $endCol);
            $metaRow = $rows[$block['meta_row']] ?? [];
            $dateRow = $rows[$block['meta_row'] + 1] ?? [];
            $name = $this->valueNearLabel($metaRow, $block['start_col'], $endCol, 'nama');
            $employeeId = $this->valueNearLabel($dateRow, $block['start_col'], $endCol, 'user id');
            $department = $this->valueNearLabel($metaRow, $block['start_col'], $endCol, 'dept');

            foreach ($dates as $date) {
                $day = (int) substr($date, 8, 2);
                $rowIndex = $dayRows[$day] ?? null;
                if ($rowIndex === null) {
                    continue;
                }

                $row = $rows[$rowIndex] ?? [];
                $times = $this->parsedTimesFromColumns($row, array_merge($timeColumns['in'], $timeColumns['out']));
                if (empty($times)) {
                    continue;
                }

                $results[] = [
                    'file' => $fileName,
                    'sheet' => $sheetName,
                    'row_index' => $rowIndex,
                    'employee_id' => $employeeId,
                    'name' => $name,
                    'department' => $department,
                    'tanggal' => $date,
                    'times' => $times,
                ];
            }
        }

        return $results;
    }

    private function parseGenericMachineRows(
        array $rows,
        Collection $users,
        int $shiftId,
        string $shiftJamMasuk,
        string $shiftJamKeluar,
        string $fileName,
        string $sheetName = ''
    ): array {
        $headerRowIndex = $this->findHeaderRow($rows);
        $columnMap = $this->detectColumns($rows[$headerRowIndex] ?? []);
        if (($columnMap['nama'] ?? null) === null && ($columnMap['employee_id'] ?? null) === null) {
            return [];
        }

        $processedRows = $this->processRows($rows, $headerRowIndex, $columnMap, $users, $shiftId, $shiftJamMasuk, $shiftJamKeluar);
        $results = [];
        foreach ($processedRows as $row) {
            if (empty($row['tanggal'])) {
                continue;
            }

            $times = array_values(array_filter([$row['jam_absen'] ?? null, $row['jam_pulang'] ?? null]));
            $results[] = [
                'file' => $fileName,
                'sheet' => $sheetName,
                'row_index' => $row['row_index'] ?? null,
                'employee_id' => (string) ($row['raw_employee_id'] ?? ''),
                'name' => (string) ($row['raw_nama'] ?? ''),
                'department' => '',
                'tanggal' => $row['tanggal'],
                'times' => $times,
                'status' => $row['status_absen'] ?? '',
                'telat' => $row['telat'] ?? 0,
                'pulang_cepat' => $row['pulang_cepat'] ?? 0,
            ];
        }

        return $results;
    }

    private function parseMachinePersonalAttendanceRows(array $rows, array $reportDateRange, string $fileName, string $sheetName = ''): array
    {
        if (empty($reportDateRange)) {
            return [];
        }

        $start = new \DateTimeImmutable($reportDateRange['start']);
        $results = [];

        for ($rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
            $row = $rows[$rowIndex];
            $meta = $this->extractMachineAttendanceMeta($row);
            if (!$meta) {
                continue;
            }

            $daysRowIndex = $rowIndex + 1;
            $daysRow = $rows[$daysRowIndex] ?? [];
            $dayColumns = [];
            foreach ($daysRow as $colIndex => $value) {
                if (preg_match('/^\d{1,2}$/', trim((string) $value))) {
                    $dayColumns[(int) $value] = $colIndex;
                }
            }

            if (empty($dayColumns)) {
                continue;
            }

            $timeRows = [];
            for ($scanRowIndex = $daysRowIndex + 1; $scanRowIndex < count($rows); $scanRowIndex++) {
                if ($this->extractMachineAttendanceMeta($rows[$scanRowIndex] ?? [])) {
                    break;
                }

                if (!$this->rowHasValueInRange($rows[$scanRowIndex] ?? [], min($dayColumns), max($dayColumns))) {
                    break;
                }

                $timeRows[$scanRowIndex] = $rows[$scanRowIndex];
            }

            foreach ($dayColumns as $day => $colIndex) {
                $date = $start->modify('+' . ($day - 1) . ' days')->format('Y-m-d');
                if ($date < $reportDateRange['start'] || $date > $reportDateRange['end']) {
                    continue;
                }

                $times = [];
                foreach ($timeRows as $scanRowIndex => $scanRow) {
                    $times = array_merge($times, $this->extractTimesFromCell($scanRow[$colIndex] ?? ''));
                }
                $times = array_values(array_unique($times));
                sort($times);

                if (empty($times)) {
                    continue;
                }

                $results[] = [
                    'file' => $fileName,
                    'sheet' => $sheetName,
                    'row_index' => $rowIndex,
                    'employee_id' => $meta['employee_id'],
                    'name' => $meta['name'],
                    'department' => $meta['department'],
                    'tanggal' => $date,
                    'times' => $times,
                ];
            }
        }

        return $results;
    }

    private function parseMachineAbnormalRows(array $rows, array $reportDateRange, string $fileName, string $sheetName = ''): array
    {
        $headerIndex = null;
        foreach ($rows as $index => $row) {
            $text = $this->normalizeText(implode(' ', $row));
            if (str_contains($text, 'user id') && str_contains($text, 'tanggal') && str_contains($text, 'terlambat')) {
                $headerIndex = $index;
                break;
            }
        }

        if ($headerIndex === null) {
            return [];
        }

        $results = [];
        $dataStart = $headerIndex + ($this->looksLikeSubHeaderRow($rows, $headerIndex) ? 2 : 1);
        for ($i = $dataStart; $i < count($rows); $i++) {
            $row = $rows[$i];
            $employeeId = $this->cleanMachineLabelValue($this->valueAt($row, 0));
            $name = $this->cleanMachineLabelValue($this->valueAt($row, 1));
            $tanggal = $this->parseDate($this->valueAt($row, 3));
            if (($employeeId === '' && $name === '') || !$tanggal) {
                continue;
            }

            $times = [];
            foreach ([4, 5, 6, 7] as $timeCol) {
                $times = array_merge($times, $this->extractTimesFromCell($row[$timeCol] ?? ''));
            }
            $times = array_values(array_unique($times));
            sort($times);

            $statusText = $this->normalizeText(implode(' ', array_slice($row, 4, 8)));
            $status = str_contains($statusText, 'cuti')
                ? 'Cuti'
                : (str_contains($statusText, 'tidak hadir') ? 'Tidak Masuk' : ($times ? 'Masuk' : ''));

            $results[] = [
                'file' => $fileName,
                'sheet' => $sheetName,
                'row_index' => $i,
                'employee_id' => $employeeId,
                'name' => $name,
                'department' => $this->cleanMachineLabelValue($this->valueAt($row, 2)),
                'tanggal' => $tanggal,
                'times' => $times,
                'status' => $status,
                'telat' => (int) round($this->parseNumber($this->valueAt($row, 8)) * 60),
                'pulang_cepat' => (int) round($this->parseNumber($this->valueAt($row, 9)) * 60),
            ];
        }

        return $results;
    }

    private function parseMachineAttendanceSummaries(array $rows, array $reportDateRange, string $fileName, string $sheetName = ''): array
    {
        $headerIndex = null;
        foreach ($rows as $index => $row) {
            $text = $this->normalizeText(implode(' ', $row));
            if (str_contains($text, 'user id') && str_contains($text, 'hari kehadiran')) {
                $headerIndex = $index;
                break;
            }
        }

        if ($headerIndex === null) {
            return [];
        }

        $headers = $this->buildHeaderLabels($rows, $headerIndex);
        $employeeIdCol = $this->findHeaderColumn($headers, ['user id', 'employee id', 'nik', 'pin']) ?? 0;
        $nameCol = $this->findHeaderColumn($headers, ['nama', 'name']) ?? 1;
        $departmentCol = $this->findHeaderColumn($headers, ['departemen', 'department', 'dept']) ?? 2;
        $attendanceCol = $this->findHeaderColumn($headers, ['hari kehadiran', 'standar/aktual', 'kehadiran standar']) ?? 11;
        $absentCol = $this->findHeaderColumn($headers, ['tidak hadir', 'absen hari', 'alpha hari']) ?? 13;
        $cutiCol = $this->findHeaderColumn($headers, ['cuti', 'leave']) ?? 14;
        $lateCountCol = $this->findHeaderColumn($headers, ['terlambat masuk'], ['kali']) ?? 5;
        $lateMinutesCol = $this->findHeaderColumn($headers, ['terlambat masuk'], ['menit']) ?? 6;

        $summaries = [];
        $dataStart = $headerIndex + ($this->looksLikeSubHeaderRow($rows, $headerIndex) ? 2 : 1);
        for ($i = $dataStart; $i < count($rows); $i++) {
            $row = $rows[$i];
            $employeeId = $this->cleanMachineLabelValue($this->valueAt($row, $employeeIdCol));
            $name = $this->cleanMachineLabelValue($this->valueAt($row, $nameCol));
            if ($employeeId === '' && $name === '') {
                continue;
            }

            $attendance = $this->parseAttendancePair($this->valueAt($row, $attendanceCol));
            $key = $this->machineEmployeeKey($employeeId, $name);
            $summaries[$key] = [
                'file' => $fileName,
                'sheet' => $sheetName,
                'row_index' => $i,
                'employee_id' => $employeeId,
                'name' => $name,
                'department' => $this->cleanMachineLabelValue($this->valueAt($row, $departmentCol)),
                'standard_days' => $attendance['standard'],
                'actual_days' => $attendance['actual'],
                'absent_days' => $this->parseNumber($this->valueAt($row, $absentCol)),
                'cuti_days' => $this->parseNumber($this->valueAt($row, $cutiCol)),
                'late_count' => (int) $this->parseNumber($this->valueAt($row, $lateCountCol)),
                'late_minutes' => (int) $this->parseNumber($this->valueAt($row, $lateMinutesCol)),
            ];
        }

        return $summaries;
    }

    private function findHeaderColumn(array $headers, array $requiredKeywords, array $optionalKeywords = []): ?int
    {
        foreach ($headers as $index => $header) {
            $text = $this->normalizeText((string) $header);
            $matched = empty($requiredKeywords);
            foreach ($requiredKeywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                continue;
            }

            foreach ($optionalKeywords as $keyword) {
                if (!str_contains($text, $keyword)) {
                    $matched = false;
                    break;
                }
            }

            if ($matched) {
                return $index;
            }
        }

        return null;
    }

    private function looksLikeWeekdayRow(array $row, array $dayColumns): bool
    {
        $score = 0;
        foreach ($dayColumns as $colIndex) {
            $value = $this->normalizeText((string) ($row[$colIndex] ?? ''));
            if (in_array($value, ['sen', 'sel', 'rab', 'kam', 'jum', 'sab', 'min', 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'], true)) {
                $score++;
            }
        }

        return $score >= min(2, max(1, count($dayColumns)));
    }

    private function parseShiftScheduleSpecialCodes(array $rows): array
    {
        $codes = [];
        $text = implode(' ', array_map(fn($row) => implode(' ', $row), array_slice($rows, 0, 6)));
        preg_match_all('/(\d+)\s*-\s*([^,;，]+)/u', $text, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $codes[$this->normalizeScheduleCode($match[1])] = $this->scheduleStatusFromLabel($match[2]);
        }

        return $codes;
    }

    private function scheduleStatusFromValue(string $rawValue, array $specialCodes): array
    {
        $value = trim($rawValue);
        if ($value === '' || $value === '-') {
            return $this->scheduleStatusPayload('Libur', 'libur');
        }

        $code = $this->normalizeScheduleCode($value);
        if (isset($specialCodes[$code])) {
            return $specialCodes[$code];
        }

        if (in_array($code, ['25', '2'], true)) {
            return $this->scheduleStatusPayload('Izin Masuk', 'business_trip', 'mapping_shifts,dinas_luars');
        }

        if (in_array($code, ['26', '3'], true)) {
            return $this->scheduleStatusPayload('Cuti', 'cuti');
        }

        $text = $this->normalizeText($value);
        if ($text === '0') {
            return $this->scheduleStatusPayload('Libur', 'libur');
        }

        if (str_contains($text, 'cuti') || str_contains($text, 'leave')) {
            return $this->scheduleStatusPayload('Cuti', 'cuti');
        }

        if (str_contains($text, 'perjalanan') || str_contains($text, 'bisnis') || str_contains($text, 'dinas')) {
            return $this->scheduleStatusPayload('Izin Masuk', 'business_trip', 'mapping_shifts,dinas_luars');
        }

        if (str_contains($text, 'sakit')) {
            return $this->scheduleStatusPayload('Sakit', 'sakit');
        }

        if (str_contains($text, 'izin')) {
            return $this->scheduleStatusPayload('Izin Masuk', 'izin');
        }

        if (str_contains($text, 'libur')) {
            return $this->scheduleStatusPayload('Libur', 'libur');
        }

        return $this->scheduleStatusPayload('Tidak Masuk', 'workday');
    }

    private function normalizeScheduleCode(string $value): string
    {
        $value = trim(str_replace(',', '.', $value));
        if (preg_match('/^\d+(?:\.0+)?$/', $value)) {
            return (string) (int) $value;
        }

        return $value;
    }

    private function scheduleStatusFromLabel(string $label): array
    {
        $text = $this->normalizeText($label);
        if (str_contains($text, 'cuti') || str_contains($text, 'leave')) {
            return $this->scheduleStatusPayload('Cuti', 'cuti');
        }

        if (str_contains($text, 'perjalanan') || str_contains($text, 'bisnis') || str_contains($text, 'dinas')) {
            return $this->scheduleStatusPayload('Izin Masuk', 'business_trip', 'mapping_shifts,dinas_luars');
        }

        if (str_contains($text, 'sakit')) {
            return $this->scheduleStatusPayload('Sakit', 'sakit');
        }

        if (str_contains($text, 'izin')) {
            return $this->scheduleStatusPayload('Izin Masuk', 'izin');
        }

        return $this->scheduleStatusPayload('Tidak Masuk', 'workday');
    }

    private function scheduleStatusPayload(string $status, string $specialType, string $targetTable = 'mapping_shifts'): array
    {
        return [
            'status' => $status,
            'special_type' => $specialType,
            'target_table' => $targetTable,
        ];
    }

    private function extractMachineAttendanceMeta(array $row): ?array
    {
        $employeeId = '';
        $name = '';
        $department = '';

        foreach ($row as $colIndex => $value) {
            $label = $this->normalizeMachineLabel((string) $value);
            if ($label === 'user id') {
                $employeeId = $this->cleanMachineLabelValue($row[$colIndex + 1] ?? '');
            } elseif ($label === 'nama') {
                $name = $this->cleanMachineLabelValue($row[$colIndex + 1] ?? '');
            } elseif ($label === 'departemen' || $label === 'dept') {
                $department = $this->cleanMachineLabelValue($row[$colIndex + 1] ?? '');
            }
        }

        if ($employeeId === '' && $name === '') {
            return null;
        }

        return [
            'employee_id' => $employeeId,
            'name' => $name,
            'department' => $department,
        ];
    }

    private function normalizeMachineLabel(string $value): string
    {
        $value = str_replace(["\xef\xbc\x9a", ':', '*', '.', '：'], '', $value);
        return $this->normalizeText($value);
    }

    private function cleanMachineLabelValue($value): string
    {
        return trim(str_replace(["\r", "\n"], ' ', (string) $value));
    }

    private function parsedTimesFromColumns(array $row, array $columns): array
    {
        $times = [];
        foreach (array_unique($columns) as $col) {
            $times = array_merge($times, $this->extractTimesFromCell($row[$col] ?? ''));
        }

        $times = array_values(array_unique(array_filter($times)));
        sort($times);

        return $times;
    }

    private function extractTimesFromCell($value): array
    {
        $value = str_replace(["\r", "\n"], ' ', (string) $value);
        if ($value === '') {
            return [];
        }

        preg_match_all('/\b\d{1,2}[:.]\d{2}(?::\d{2})?\b/', $value, $matches);
        $times = [];
        foreach ($matches[0] as $match) {
            $time = $this->parseTime($match);
            if ($time !== null) {
                $times[] = $time;
            }
        }

        if (empty($times)) {
            $time = $this->parseTime($value);
            if ($time !== null) {
                $times[] = $time;
            }
        }

        return array_values(array_unique($times));
    }

    private function machineEmployeeKey(string $employeeId, string $name): string
    {
        $employeeId = trim($employeeId);
        if ($employeeId !== '') {
            return 'id:' . $this->normalizeText($employeeId);
        }

        return 'name:' . $this->normalizeText($name);
    }

    private function mergeMachineEmployee(array $existing, array $incoming, array $directory): array
    {
        $employeeId = $incoming['employee_id'] ?? ($existing['employee_id'] ?? '');
        $directoryRow = $employeeId !== '' ? ($directory[$employeeId] ?? []) : [];

        return [
            'employee_id' => $employeeId,
            'name' => $incoming['name'] ?: ($existing['name'] ?? ($directoryRow['name'] ?? '')),
            'department' => $incoming['department'] ?: ($existing['department'] ?? ($directoryRow['department'] ?? '')),
        ];
    }

    private function datesFromMachineRecords(array $records): array
    {
        $dates = [];
        foreach ($records as $employeeRecords) {
            foreach (array_keys($employeeRecords) as $date) {
                $dates[$date] = true;
            }
        }

        $dates = array_keys($dates);
        sort($dates);

        return $dates;
    }

    private function classifyMachineTimes(array $times, string $date, string $shiftJamMasuk, string $shiftJamKeluar): array
    {
        $times = array_values(array_filter($times));
        sort($times);
        $jamMasuk = null;
        $jamPulang = null;

        if (count($times) >= 2) {
            $jamMasuk = $times[0];
            $jamPulang = end($times);
            if ($jamPulang === $jamMasuk) {
                $jamPulang = null;
            }
        } elseif (count($times) === 1) {
            $only = $times[0];
            $midpoint = strtotime($date . ' ' . $shiftJamMasuk)
                + ((strtotime($date . ' ' . $shiftJamKeluar) - strtotime($date . ' ' . $shiftJamMasuk)) / 2);

            if (strtotime($date . ' ' . $only) >= $midpoint) {
                $jamPulang = $only;
            } else {
                $jamMasuk = $only;
            }
        }

        return [
            'has_scan' => count($times) > 0,
            'status' => count($times) > 0 ? 'Masuk' : 'Libur',
            'jam_absen' => $jamMasuk,
            'jam_pulang' => $jamPulang,
            'telat' => $jamMasuk ? $this->hitungTelat($date, $shiftJamMasuk, $jamMasuk) : 0,
            'pulang_cepat' => $jamPulang ? $this->hitungPulangCepat($date, $shiftJamKeluar, $jamPulang) : 0,
        ];
    }

    private function reconcileMachineRowsWithSummary(array $rows, array $summary): array
    {
        $targetMasuk = (int) round((float) ($summary['actual_days'] ?? 0));
        if ($targetMasuk < 0) {
            return $rows;
        }

        $counts = $this->machineResultStatusCounts($rows);
        $excessMasuk = $counts['masuk'] - $targetMasuk;
        if ($excessMasuk <= 0) {
            return $rows;
        }

        $candidateIndexes = [];
        foreach ($rows as $index => $row) {
            if ($this->isIncompleteMachineScanRow($row)) {
                $candidateIndexes[] = $index;
            }
        }

        usort($candidateIndexes, fn($a, $b) => strcmp((string) ($rows[$a]['tanggal'] ?? ''), (string) ($rows[$b]['tanggal'] ?? '')));

        foreach ($candidateIndexes as $index) {
            if ($excessMasuk <= 0) {
                break;
            }

            $rows[$index] = $this->markIncompleteMachineScanAsAbsent($rows[$index]);
            $excessMasuk--;
        }

        return $rows;
    }

    private function isIncompleteMachineScanRow(array $row): bool
    {
        if (($row['source_format'] ?? '') !== 'machine_package') {
            return false;
        }

        if (!in_array($row['status_absen'] ?? '', ['Masuk', 'Izin Telat', 'Izin Pulang Cepat'], true)) {
            return false;
        }

        if (str_contains((string) ($row['target_table'] ?? ''), 'dinas_luars')) {
            return false;
        }

        $hasCheckIn = !empty($row['jam_absen']);
        $hasCheckOut = !empty($row['jam_pulang']);

        return $hasCheckIn xor $hasCheckOut;
    }

    private function markIncompleteMachineScanAsAbsent(array $row): array
    {
        $notes = $row['conflict_notes'] ?? [];
        $notes[] = 'Scan masuk/pulang tidak lengkap; status disesuaikan dengan rekap mesin.';

        $row['status_absen'] = 'Tidak Masuk';
        $row['raw_status'] = 'Tidak Masuk';
        $row['telat'] = 0;
        $row['pulang_cepat'] = 0;
        $row['conflict_notes'] = array_values(array_unique($notes));
        $row['special_type'] = 'incomplete_scan';
        $row['source_priority'] = min((int) ($row['source_priority'] ?? 100), 95);

        if (isset($row['raw_columns']) && is_array($row['raw_columns'])) {
            $row['raw_columns']['Status Source'] = 'Tidak Masuk';
            $row['raw_columns']['Conflict'] = implode(' ', $row['conflict_notes']);
        }

        return $row;
    }

    private function machineResultStatusCounts(array $rows): array
    {
        $counts = [
            'masuk' => 0,
            'tidak_masuk' => 0,
            'cuti' => 0,
            'workdays' => 0,
        ];

        foreach ($rows as $row) {
            $status = $row['status_absen'] ?? '';
            if (in_array($status, ['Masuk', 'Izin Telat', 'Izin Pulang Cepat'], true)) {
                $counts['masuk']++;
                $counts['workdays']++;
            } elseif ($status === 'Tidak Masuk') {
                $counts['tidak_masuk']++;
                $counts['workdays']++;
            } elseif ($status === 'Cuti') {
                $counts['cuti']++;
                $counts['workdays']++;
            }
        }

        return $counts;
    }

    private function machineSummaryWarnings(string $name, array $summary, array $actual): array
    {
        $warnings = [];
        if (abs((float) $summary['actual_days'] - (float) $actual['masuk']) > 0.01) {
            $warnings[] = $name . ': total masuk hasil import ' . $actual['masuk'] . ' berbeda dari laporan mesin ' . $summary['actual_days'] . '.';
        }
        if (abs((float) $summary['absent_days'] - (float) $actual['tidak_masuk']) > 0.01) {
            $warnings[] = $name . ': total tidak masuk hasil import ' . $actual['tidak_masuk'] . ' berbeda dari laporan mesin ' . $summary['absent_days'] . '.';
        }
        if (abs((float) $summary['cuti_days'] - (float) $actual['cuti']) > 0.01) {
            $warnings[] = $name . ': total cuti hasil import ' . $actual['cuti'] . ' berbeda dari laporan mesin ' . $summary['cuti_days'] . '.';
        }

        return $warnings;
    }

    private function normalizeText(string $value): string
    {
        $value = strtolower(trim($value));
        return preg_replace('/\s+/', ' ', $value) ?? '';
    }

    private function normalizeResultRow(array $row): array
    {
        $defaults = [
            'preview_key' => null,
            'row_index' => null,
            'source_format' => 'unknown',
            'raw_columns' => [],
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
            'shift_id' => null,
            'user_id' => null,
            'user_name' => null,
            'confidence' => 0,
            'match_type' => 'not_found',
            'valid' => false,
            'errors' => [],
            'source_sheet' => '',
            'source_priority' => 0,
            'source_confidence' => 0,
            'target_table' => 'mapping_shifts',
            'conflict_notes' => [],
            'special_type' => null,
        ];

        $normalized = array_merge($defaults, array_intersect_key($row, $defaults));
        $normalized['errors'] = array_values(array_filter((array) $normalized['errors']));
        $normalized['conflict_notes'] = array_values(array_filter((array) $normalized['conflict_notes']));
        $normalized['raw_columns'] = (array) $normalized['raw_columns'];
        if ($normalized['preview_key'] === null && $normalized['row_index'] !== null) {
            $normalized['preview_key'] = (string) $normalized['row_index'];
        }

        return array_replace(array_flip($this->normalizedKeys), $normalized);
    }
}
