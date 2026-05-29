<?php

namespace Tests\Unit;

use App\Models\User;
use App\Services\SmartAbsenParser;
use Illuminate\Support\Collection;
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

    private function users(array $attributes): Collection
    {
        return new Collection(array_map(function (array $userAttributes): User {
            $user = new User();
            $user->setRawAttributes($userAttributes, true);
            return $user;
        }, $attributes));
    }
}
