<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class SmartAbsenParser
{
    private array $normalizedKeys = [
        'row_index',
        'source_format',
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
    ];

    /**
     * Keyword mapping untuk deteksi kolom otomatis
     */
    private array $columnKeywords = [
        'nama'        => ['nama', 'name', 'karyawan', 'pegawai', 'employee', 'no name', 'nama pegawai', 'nama karyawan'],
        'employee_id' => ['employee id', 'emp id', 'pin', 'nik', 'nip', 'id karyawan', 'kode karyawan', 'no pegawai'],
        'tanggal'     => ['tanggal', 'date', 'tgl', 'hari', 'tanggal absen'],
        'datetime'    => ['datetime', 'date time', 'tanggal jam', 'waktu scan', 'scan time', 'punch time', 'record time', 'clock time'],
        'jam_masuk'   => ['jam masuk', 'check in', 'checkin', 'masuk kerja', 'clock in', 'time in', 'jam datang'],
        'jam_pulang'  => ['jam pulang', 'check out', 'checkout', 'clock out', 'time out', 'jam keluar', 'jam pergi'],
        'jam'         => ['jam', 'time', 'waktu', 'scan', 'punch'],
        'status'      => ['status', 'keterangan', 'ket', 'info', 'hadir', 'kehadiran', 'state', 'verify state'],
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
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $rows = [];
        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();

        for ($row = 1; $row <= $highestRow; $row++) {
            $rowData = [];
            for ($col = 'A'; $col <= $highestCol; $col++) {
                $cell = $sheet->getCell($col . $row);
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
        ];

        foreach ($headerRow as $colIndex => $header) {
            $headerLower = $this->normalizeText($header);
            foreach ($this->columnKeywords as $key => $keywords) {
                if ($detected[$key] === null) {
                    foreach ($keywords as $keyword) {
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

    private function looksLikeEventLog(array $columnMap): bool
    {
        $hasSingleTime = ($columnMap['datetime'] ?? null) !== null || (
            ($columnMap['tanggal'] ?? null) !== null
            && ($columnMap['jam'] ?? null) !== null
            && ($columnMap['jam_masuk'] ?? null) === null
            && ($columnMap['jam_pulang'] ?? null) === null
        );

        return $hasSingleTime && (($columnMap['nama'] ?? null) !== null || ($columnMap['employee_id'] ?? null) !== null);
    }

    private function valueAt(array $row, ?int $index): string
    {
        return $index !== null && array_key_exists($index, $row) ? trim((string) $row[$index]) : '';
    }

    private function normalizeText(string $value): string
    {
        $value = strtolower(trim($value));
        return preg_replace('/\s+/', ' ', $value) ?? '';
    }

    private function normalizeResultRow(array $row): array
    {
        $defaults = [
            'row_index' => null,
            'source_format' => 'unknown',
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
        ];

        $normalized = array_merge($defaults, array_intersect_key($row, $defaults));
        $normalized['errors'] = array_values(array_filter((array) $normalized['errors']));

        return array_replace(array_flip($this->normalizedKeys), $normalized);
    }
}
