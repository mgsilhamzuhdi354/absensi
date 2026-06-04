<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\SmartAbsenParser;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class SmartAbsenParserTest extends TestCase
{
    /** @test */
    public function it_parses_daily_attendance_template(): void
    {
        $parser = new SmartAbsenParser();
        $users = $this->users([
            ['id' => 1, 'name' => 'Siti Aminah', 'username' => 'siti'],
        ]);

        $rows = [
            ['Nama Karyawan', 'Tanggal', 'Jam Masuk', 'Jam Pulang', 'Status'],
            ['Siti Aminah', '01/05/2026', '08:10', '17:05', 'Hadir'],
        ];

        $headerRow = $parser->findHeaderRow($rows);
        $columnMap = $parser->detectColumns($rows[$headerRow]);
        $result = $parser->processRows($rows, $headerRow, $columnMap, $users, 1, '08:00:00', '17:00:00');

        $this->assertCount(1, $result);
        $this->assertSame('2026-05-01', $result[0]['tanggal']);
        $this->assertSame('08:10', $result[0]['jam_absen']);
        $this->assertSame('17:05', $result[0]['jam_pulang']);
        $this->assertSame(600, $result[0]['telat']);
        $this->assertTrue($result[0]['valid']);
    }

    /** @test */
    public function it_groups_machine_event_log_rows_into_one_attendance_record(): void
    {
        $parser = new SmartAbsenParser();
        $users = $this->users([
            ['id' => 2, 'name' => 'Budi Santoso', 'username' => 'budi', 'employee_id' => 'EMP001'],
        ]);

        $rows = [
            ['PIN', 'Name', 'Scan Time', 'State'],
            ['EMP001', 'Budi Santoso', '2026-05-01 07:55:00', 'Check In'],
            ['EMP001', 'Budi Santoso', '2026-05-01 12:00:00', 'Break'],
            ['EMP001', 'Budi Santoso', '2026-05-01 17:10:00', 'Check Out'],
        ];

        $headerRow = $parser->findHeaderRow($rows);
        $columnMap = $parser->detectColumns($rows[$headerRow]);
        $result = $parser->processRows($rows, $headerRow, $columnMap, $users, 1, '08:00:00', '17:00:00');

        $this->assertCount(1, $result);
        $this->assertSame('2026-05-01', $result[0]['tanggal']);
        $this->assertSame('07:55', $result[0]['jam_absen']);
        $this->assertSame('17:10', $result[0]['jam_pulang']);
        $this->assertSame('employee_id', $result[0]['match_type']);
        $this->assertTrue($result[0]['valid']);
    }

    /** @test */
    public function it_can_match_by_username_when_employee_id_column_contains_username(): void
    {
        $parser = new SmartAbsenParser();
        $users = $this->users([
            ['id' => 3, 'name' => 'Rina Putri', 'username' => 'rina'],
        ]);

        $rows = [
            ['NIP', 'Tanggal', 'Jam Masuk', 'Jam Pulang'],
            ['rina', '2026-05-02', '0730', '17.00'],
        ];

        $headerRow = $parser->findHeaderRow($rows);
        $columnMap = $parser->detectColumns($rows[$headerRow]);
        $result = $parser->processRows($rows, $headerRow, $columnMap, $users, 1, '08:00:00', '17:00:00');

        $this->assertCount(1, $result);
        $this->assertSame(3, $result[0]['user_id']);
        $this->assertSame('07:30', $result[0]['jam_absen']);
        $this->assertSame('17:00', $result[0]['jam_pulang']);
        $this->assertTrue($result[0]['valid']);
    }

    /** @test */
    public function all_supported_templates_return_the_same_normalized_output_shape(): void
    {
        $parser = new SmartAbsenParser();
        $users = $this->users([
            ['id' => 4, 'name' => 'Dewi Lestari', 'username' => 'dewi', 'employee_id' => 'EMP004'],
        ]);

        $dailyRows = [
            ['Nama Karyawan', 'Tanggal', 'Jam Masuk', 'Jam Pulang', 'Status'],
            ['Dewi Lestari', '01/05/2026', '08:00', '17:00', 'Hadir'],
        ];

        $eventRows = [
            ['PIN', 'Name', 'Scan Time', 'State'],
            ['EMP004', 'Dewi Lestari', '2026-05-01 08:00:00', 'Check In'],
            ['EMP004', 'Dewi Lestari', '2026-05-01 17:00:00', 'Check Out'],
        ];

        $dailyHeader = $parser->findHeaderRow($dailyRows);
        $dailyMap = $parser->detectColumns($dailyRows[$dailyHeader]);
        $dailyResult = $parser->processRows($dailyRows, $dailyHeader, $dailyMap, $users, 1, '08:00:00', '17:00:00');

        $eventHeader = $parser->findHeaderRow($eventRows);
        $eventMap = $parser->detectColumns($eventRows[$eventHeader]);
        $eventResult = $parser->processRows($eventRows, $eventHeader, $eventMap, $users, 1, '08:00:00', '17:00:00');

        $this->assertSame(array_keys($dailyResult[0]), array_keys($eventResult[0]));
        $this->assertSame('daily', $dailyResult[0]['source_format']);
        $this->assertSame('event_log', $eventResult[0]['source_format']);
    }

    /** @test */
    public function it_builds_raw_preview_with_all_excel_columns(): void
    {
        $parser = new SmartAbsenParser();
        $rows = [
            ['Analisa Kehadiran'],
            ['Tanggal Statistik:2026-06-01~2026-06-02'],
            ['User ID.', 'Nama', 'Departemen', 'Jam Kerja', 'Jam Kerja', 'Tidak hadir (hari)'],
            ['', '', '', 'Standar', 'Aktual', ''],
            ['1', 'mgs ilham zuhdi', 'it', '18,0', '0,0', '2'],
        ];

        $headerRow = $parser->findHeaderRow($rows);
        $rawPreview = $parser->buildRawPreview($rows, $headerRow);

        $this->assertSame(6, $rawPreview['total_columns']);
        $this->assertSame(1, $rawPreview['total_rows']);
        $this->assertSame('Jam Kerja - Standar', $rawPreview['headers'][3]);
        $this->assertSame('Jam Kerja - Aktual', $rawPreview['headers'][4]);
        $this->assertSame('mgs ilham zuhdi', $rawPreview['rows'][0]['columns']['Nama']);
        $this->assertSame('2', $rawPreview['rows'][0]['columns']['Tidak hadir (hari)']);
    }

    /** @test */
    public function it_expands_attendance_analysis_absent_range_into_system_rows(): void
    {
        $parser = new SmartAbsenParser();
        $users = $this->users([
            ['id' => 5, 'name' => 'MGS ILHAM ZUHDI', 'username' => 'mgs'],
        ]);
        $rows = [
            ['Analisa Kehadiran'],
            ['Tanggal Statistik:2026-06-01~2026-06-02'],
            ['User ID.', 'Nama', 'Departemen', 'Hari Kehadiran (Standar/Aktual)', 'Tidak hadir (hari)', 'Cuti (hari)'],
            ['1', 'mgs ilham zuhdi', 'it', '2/0', '2', '0'],
        ];

        $headerRow = $parser->findHeaderRow($rows);
        $columnMap = $parser->detectColumns($rows[$headerRow]);
        $result = $parser->processRows($rows, $headerRow, $columnMap, $users, 1, '08:00:00', '17:00:00');

        $this->assertCount(2, $result);
        $this->assertSame('attendance_analysis', $result[0]['source_format']);
        $this->assertSame('2026-06-01', $result[0]['tanggal']);
        $this->assertSame('2026-06-02', $result[1]['tanggal']);
        $this->assertSame('Tidak Masuk', $result[0]['status_absen']);
        $this->assertSame(5, $result[0]['user_id']);
        $this->assertTrue($result[0]['valid']);
    }

    /** @test */
    public function it_does_not_treat_attendance_analysis_summary_columns_as_event_log(): void
    {
        $parser = new SmartAbsenParser();
        $users = $this->users([
            ['id' => 5, 'name' => 'MGS ILHAM ZUHDI', 'username' => 'mgs'],
        ]);
        $rows = [
            ['Analisa Kehadiran'],
            ['Tanggal Statistik:2026-06-01~2026-06-02'],
            [
                'User ID.',
                'Nama',
                'Departemen',
                'Jam Kerja',
                'Jam Kerja',
                'Terlambat Masuk',
                'Terlambat Masuk',
                'Keluar Lebih Awal',
                'Keluar Lebih Awal',
                'Jam Kerja Lembur',
                'Jam Kerja Lembur',
                'Hari Kehadiran (Standar/Aktual)',
                'Perjalanan Bisnis (hari)',
                'Tidak hadir (hari)',
                'Cuti (hari)',
                'Presentase Kehadiran',
            ],
            [
                '',
                '',
                '',
                'Standar',
                'Aktual',
                'Kali',
                'Menit',
                'Kali',
                'Menit',
                'Normal',
                'Khusus',
                '',
                '',
                '',
                '',
                '',
            ],
            ['1', 'mgs ilham zuhdi', 'it', '18,0', '0,0', '0', '0', '0', '0', '0,0', '0,0', '2/0', '0', '2', '0', '-'],
        ];

        $headerRow = $parser->findHeaderRow($rows);
        $columnMap = $parser->detectColumns($rows[$headerRow]);
        $result = $parser->processRows($rows, $headerRow, $columnMap, $users, 1, '08:00:00', '17:00:00');

        $this->assertNull($columnMap['jam']);
        $this->assertCount(2, $result);
        $this->assertSame('attendance_analysis', $result[0]['source_format']);
        $this->assertSame('2026-06-01', $result[0]['tanggal']);
        $this->assertSame('2026-06-02', $result[1]['tanggal']);
        $this->assertSame('Tidak Masuk', $result[0]['status_absen']);
        $this->assertTrue($result[0]['valid']);
    }

    /** @test */
    public function it_parses_wide_personal_attendance_blocks_with_daily_scan_times(): void
    {
        $parser = new SmartAbsenParser();
        $users = $this->users([
            ['id' => 6, 'name' => 'MGS ILHAM ZUHDI', 'username' => 'mgs'],
            ['id' => 7, 'name' => 'DANIEL', 'username' => 'daniel'],
        ]);
        $rows = array_fill(0, 13, array_fill(0, 24, ''));
        $rows[0][10] = 'Catatan Kehadiran Karyawan';
        $rows[1][20] = 'Tanggal Kehadiran:2026-06-01~2026-06-02';

        $this->fillAttendanceBlock($rows, 0, 'it', 'mgs ilham zuhdi', '1', [
            '02 Sel' => ['08:05', '', '', '', '', ''],
        ]);
        $this->fillAttendanceBlock($rows, 11, 'ops', 'daniel', '2', [
            '02 Sel' => ['08:10', '17:00', '', '', '', ''],
        ]);

        $headerRow = $parser->findHeaderRow($rows);
        $columnMap = $parser->detectColumns($rows[$headerRow]);
        $result = $parser->processRows($rows, $headerRow, $columnMap, $users, 1, '08:00:00', '17:00:00');

        $this->assertCount(4, $result);
        $mgsSecondDay = collect($result)->first(fn($row) => $row['user_id'] === 6 && $row['tanggal'] === '2026-06-02');
        $danielSecondDay = collect($result)->first(fn($row) => $row['user_id'] === 7 && $row['tanggal'] === '2026-06-02');

        $this->assertSame('personal_attendance_blocks', $mgsSecondDay['source_format']);
        $this->assertSame('08:05', $mgsSecondDay['jam_absen']);
        $this->assertSame('Masuk', $mgsSecondDay['status_absen']);
        $this->assertTrue($mgsSecondDay['valid']);
        $this->assertSame('08:10', $danielSecondDay['jam_absen']);
        $this->assertSame('17:00', $danielSecondDay['jam_pulang']);
    }

    /** @test */
    public function it_reads_excel_columns_after_z(): void
    {
        $parser = new SmartAbsenParser();
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'Awal');
        $sheet->setCellValue('AT1', 'Kolom Jauh');

        $path = tempnam(sys_get_temp_dir(), 'smart_parser_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        $rows = $parser->parseExcel($path);
        @unlink($path);

        $this->assertSame('Awal', $rows[0][0]);
        $this->assertSame('Kolom Jauh', $rows[0][45]);
    }

    /** @test */
    public function it_processes_machine_package_files_into_daily_mapping_rows(): void
    {
        $parser = new SmartAbsenParser();
        $users = $this->users([
            ['id' => 8, 'name' => 'MGS ILHAM ZUHDI', 'username' => 'mgs'],
        ]);

        $files = [
            $this->makeXlsx([
                ['Catatan Kehadiran Karyawan'],
                ['Tanggal Kehadiran:2026-05-01~2026-05-04'],
                ['', '', '', '', 'User ID.：', '1', '', '', 'Nama：', 'mgs ilham zuhdi', '', '', 'Departemen：', 'it'],
                ['1', '2', '3', '4'],
                ['', "07:57\n17:00", '18:10', '08:02'],
                ['', '17:01', '', ''],
            ]),
            $this->makeXlsx([
                ['Kehadiran Tidak Normal'],
                ['Tanggal Kehadiran:2026-05-01~2026-05-04'],
                ['User ID.', 'Nama', 'Departemen', 'Tanggal', 'Pagi', 'Siang', 'Lembur', 'Keterangan', 'Terlambat Masuk', 'Keluar Lebih Awal'],
                ['1', 'mgs ilham zuhdi', 'it', '2026-05-01', 'Tidak hadir', '', '', '', '0', '0'],
                ['1', 'mgs ilham zuhdi', 'it', '2026-05-04', '08:02', '', '', '', '2', '0'],
            ]),
            $this->makeXlsx([
                ['Analisa Kehadiran'],
                ['Tanggal Statistik:2026-05-01~2026-05-04'],
                ['User ID.', 'Nama', 'Departemen', 'Jam Kerja', 'Jam Kerja', 'Terlambat Masuk', 'Terlambat Masuk', 'Keluar Lebih Awal', 'Keluar Lebih Awal', 'Jam Kerja Lembur', 'Jam Kerja Lembur', 'Hari Kehadiran (Standar/Aktual)', 'Perjalanan Bisnis (hari)', 'Tidak hadir (hari)', 'Cuti (hari)'],
                ['', '', '', 'Standar', 'Aktual', 'Kali', 'Menit', 'Kali', 'Menit', 'Normal', 'Khusus', '', '', '', ''],
                ['1', 'mgs ilham zuhdi', 'it', '4,0', '3,0', '1', '2', '0', '0', '0,0', '0,0', '4/3', '0', '1', '0'],
            ]),
            $this->makeXlsx([
                ['Informasi Pengguna'],
                ['User ID.', 'Nama', 'Departemen'],
                ['1', 'mgs ilham zuhdi', 'it'],
            ]),
        ];

        try {
            $result = $parser->processMachineFiles($files, $users, 1, '08:00:00', '17:00:00');
        } finally {
            foreach ($files as $file) {
                @unlink($file);
            }
        }

        $rows = collect($result['results']);
        $this->assertSame(['start' => '2026-05-01', 'end' => '2026-05-04'], $result['date_range']);
        $this->assertCount(4, $rows);
        $this->assertSame('Tidak Masuk', $rows->firstWhere('tanggal', '2026-05-01')['status_absen']);

        $secondDay = $rows->firstWhere('tanggal', '2026-05-02');
        $this->assertSame('07:57', $secondDay['jam_absen']);
        $this->assertSame('17:01', $secondDay['jam_pulang']);

        $thirdDay = $rows->firstWhere('tanggal', '2026-05-03');
        $this->assertNull($thirdDay['jam_absen']);
        $this->assertSame('18:10', $thirdDay['jam_pulang']);
        $this->assertSame('Masuk', $thirdDay['status_absen']);

        $fourthDay = $rows->firstWhere('tanggal', '2026-05-04');
        $this->assertSame(120, $fourthDay['telat']);
        $this->assertTrue($fourthDay['valid']);
        $this->assertSame([], $result['warnings']);
    }

    private function fillAttendanceBlock(array &$rows, int $startCol, string $dept, string $name, string $userId, array $days): void
    {
        $rows[2][$startCol] = 'Dept';
        $rows[2][$startCol + 1] = $dept;
        $rows[2][$startCol + 3] = 'Nama';
        $rows[2][$startCol + 4] = $name;
        $rows[3][$startCol] = 'Tanggal';
        $rows[3][$startCol + 1] = '2026-06-01~2026-06-02';
        $rows[3][$startCol + 3] = 'User ID';
        $rows[3][$startCol + 4] = $userId;
        $rows[7][$startCol] = 'Catatan Kehadiran';
        $rows[8][$startCol] = 'Tanggal/Minggu';
        $rows[8][$startCol + 1] = 'Pagi';
        $rows[8][$startCol + 3] = 'Siang';
        $rows[8][$startCol + 5] = 'Lembur';
        $rows[9][$startCol + 1] = 'Jam Masuk';
        $rows[9][$startCol + 2] = 'Jam Keluar';
        $rows[9][$startCol + 3] = 'Jam Masuk';
        $rows[9][$startCol + 4] = 'Jam Keluar';
        $rows[9][$startCol + 5] = 'Jam Masuk';
        $rows[9][$startCol + 6] = 'Jam Keluar';
        $rows[10][$startCol] = '01 Sen';
        $rows[11][$startCol] = '02 Sel';

        foreach ($days as $label => $times) {
            $rowIndex = str_starts_with($label, '01') ? 10 : 11;
            foreach (array_values($times) as $index => $time) {
                $rows[$rowIndex][$startCol + 1 + $index] = $time;
            }
        }
    }

    private function makeXlsx(array $rows): string
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray($rows);

        $path = tempnam(sys_get_temp_dir(), 'smart_machine_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    private function users(array $attributes): Collection
    {
        return new Collection(array_map(function (array $userAttributes): User {
            $user = new User();
            $user->setRawAttributes($userAttributes, true);
            return $user;
        }, $attributes));
    }
}
