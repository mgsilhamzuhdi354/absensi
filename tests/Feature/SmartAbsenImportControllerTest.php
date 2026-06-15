<?php

namespace Tests\Feature;

use App\Models\MappingShift;
use App\Models\Lokasi;
use App\Models\Shift;
use App\Models\User;
use App\Models\dinasLuar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class SmartAbsenImportControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function preview_returns_raw_excel_columns_before_import()
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@example.test',
            'is_admin' => 'admin',
        ]);

        User::create([
            'name' => 'MGS ILHAM ZUHDI',
            'username' => 'mgs',
            'email' => 'mgs@example.test',
            'is_admin' => 'user',
        ]);

        $shift = Shift::create([
            'nama_shift' => 'Pagi',
            'jam_masuk' => '08:00:00',
            'jam_keluar' => '17:00:00',
        ]);

        $filePath = $this->makeAttendanceAnalysisXlsx();
        $file = new UploadedFile(
            $filePath,
            'analisa-kehadiran.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($admin)->postJson('/smart-import-absen/preview', [
            'file_absen' => $file,
            'shift_id' => $shift->id,
        ]);

        @unlink($filePath);

        $response->assertOk()
            ->assertJsonPath('raw_preview.total_columns', 5)
            ->assertJsonPath('preview.0.source_format', 'machine_package_summary')
            ->assertJsonPath('preview.0.valid', false)
            ->assertJsonPath('stats.invalid', 1)
            ->assertJsonPath('stats.will_create', 0);
    }

    /** @test */
    public function import_update_does_not_clear_existing_clock_times_when_uploaded_row_is_empty()
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@example.test',
            'is_admin' => 'admin',
        ]);

        $employee = User::create([
            'name' => 'Employee',
            'username' => 'employee',
            'email' => 'employee@example.test',
            'is_admin' => 'user',
        ]);

        $shift = Shift::create([
            'nama_shift' => 'Pagi',
            'jam_masuk' => '08:00:00',
            'jam_keluar' => '17:00:00',
        ]);

        $existing = MappingShift::create([
            'user_id' => $employee->id,
            'shift_id' => $shift->id,
            'tanggal' => '2026-05-29',
            'jam_absen' => '08:05',
            'jam_pulang' => '17:10',
            'telat' => 300,
            'pulang_cepat' => 0,
            'status_absen' => 'Hadir',
            'keterangan_masuk' => 'QR Absen',
            'keterangan_pulang' => 'QR Pulang',
        ]);

        $response = $this->actingAs($admin)->postJson('/smart-import-absen/import', [
            'import_rows' => [[
                'user_id' => $employee->id,
                'shift_id' => $shift->id,
                'tanggal' => '2026-05-29',
                'jam_absen' => null,
                'jam_pulang' => null,
                'status_absen' => 'Hadir',
                'telat' => 0,
                'pulang_cepat' => 0,
            ]],
        ]);

        $response->assertOk()
            ->assertJsonPath('stats.updated', 1);

        $existing->refresh();
        $this->assertSame('08:05', $existing->jam_absen);
        $this->assertSame('17:10', $existing->jam_pulang);
        $this->assertSame('300', $existing->telat);
        $this->assertSame('0', $existing->pulang_cepat);
        $this->assertSame('QR Absen', $existing->keterangan_masuk);
        $this->assertSame('QR Pulang', $existing->keterangan_pulang);
    }

    /** @test */
    public function import_skips_machine_summary_rows_so_rekap_does_not_become_fake_attendance()
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin-summary@example.test',
            'is_admin' => 'admin',
        ]);

        $employee = User::create([
            'name' => 'Employee',
            'username' => 'employee-summary',
            'email' => 'employee-summary@example.test',
            'is_admin' => 'user',
        ]);

        $shift = Shift::create([
            'nama_shift' => 'Pagi',
            'jam_masuk' => '08:00:00',
            'jam_keluar' => '17:00:00',
        ]);

        $this->actingAs($admin)->postJson('/smart-import-absen/import', [
            'import_rows' => [[
                'source_format' => 'machine_package_summary',
                'user_id' => $employee->id,
                'shift_id' => $shift->id,
                'tanggal' => '2026-05-01',
                'jam_absen' => null,
                'jam_pulang' => null,
                'status_absen' => 'Masuk',
                'telat' => 0,
                'pulang_cepat' => 0,
            ]],
        ])->assertOk()
            ->assertJsonPath('stats.created', 0)
            ->assertJsonPath('stats.skipped', 1);

        $this->assertSame(0, MappingShift::count());
    }

    /** @test */
    public function preview_accepts_multiple_machine_files()
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@example.test',
            'is_admin' => 'admin',
        ]);

        $lokasi = Lokasi::create([
            'nama_lokasi' => 'Office',
            'lat_kantor' => '-6.200000',
            'long_kantor' => '106.816666',
        ]);

        $employee = User::create([
            'name' => 'MGS ILHAM ZUHDI',
            'username' => 'mgs',
            'email' => 'mgs@example.test',
            'is_admin' => 'user',
            'lokasi_id' => $lokasi->id,
        ]);

        $shift = Shift::create([
            'nama_shift' => 'Pagi',
            'jam_masuk' => '08:00:00',
            'jam_keluar' => '17:00:00',
        ]);

        $catatanPath = $this->makeMachineCatatanXlsx();
        $abnormalPath = $this->makeMachineAbnormalXlsx();
        $catatanFile = new UploadedFile(
            $catatanPath,
            'Catatan Kehadiran Karyawan.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
        $abnormalFile = new UploadedFile(
            $abnormalPath,
            'Kehadiran Tidak Normal.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($admin)->postJson('/smart-import-absen/preview', [
            'file_absen' => [$catatanFile, $abnormalFile],
            'shift_id' => $shift->id,
        ]);

        @unlink($catatanPath);
        @unlink($abnormalPath);

        $response->assertOk()
            ->assertJsonPath('preview.0.source_format', 'machine_package')
            ->assertJsonPath('preview.0.tanggal', '2026-05-01')
            ->assertJsonPath('preview.0.status_absen', 'Tidak Masuk')
            ->assertJsonPath('preview.1.jam_absen', '07:57')
            ->assertJsonPath('preview.1.jam_pulang', '17:01')
            ->assertJsonPath('machine.files.0.type', 'personal_attendance')
            ->assertJsonPath('machine.files.1.type', 'abnormal_attendance');

        $importRows = collect($response->json('preview'))
            ->where('valid', true)
            ->values()
            ->all();

        $this->actingAs($admin)->postJson('/smart-import-absen/import', [
            'import_rows' => $importRows,
        ])->assertOk()
            ->assertJsonPath('stats.created', 2)
            ->assertJsonPath('stats.clock_rows', 1)
            ->assertJsonPath('date_range.start', '2026-05-01')
            ->assertJsonPath('date_range.end', '2026-05-02');

        $scanRow = MappingShift::where('user_id', $employee->id)
            ->where('tanggal', '2026-05-02')
            ->firstOrFail();
        $this->assertSame('07:57', $scanRow->jam_absen);
        $this->assertSame('17:01', $scanRow->jam_pulang);
        $this->assertSame('-6.200000', $scanRow->lat_absen);
        $this->assertSame('106.816666', $scanRow->long_absen);
        $this->assertSame('-6.200000', $scanRow->lat_pulang);
        $this->assertSame('106.816666', $scanRow->long_pulang);
        $this->assertSame('Smart Import Absensi (Scan Mesin)', $scanRow->keterangan_masuk);
    }

    /** @test */
    public function preview_and_import_normalizes_multi_sheet_attendance_workbook()
    {
        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin-universal',
            'email' => 'admin-universal@example.test',
            'is_admin' => 'admin',
        ]);

        $lokasi = Lokasi::create([
            'nama_lokasi' => 'Office',
            'lat_kantor' => '-6.200000',
            'long_kantor' => '106.816666',
        ]);

        $employee = User::create([
            'name' => 'MGS ILHAM ZUHDI',
            'username' => 'mgs-universal',
            'employee_id' => '1',
            'email' => 'mgs-universal@example.test',
            'is_admin' => 'user',
            'lokasi_id' => $lokasi->id,
        ]);

        $shift = Shift::create([
            'nama_shift' => 'Pagi',
            'jam_masuk' => '08:00:00',
            'jam_keluar' => '17:00:00',
        ]);

        $path = $this->makeUniversalAttendanceWorkbookXlsx();
        $file = new UploadedFile(
            $path,
            'Lapora Kehadiran Mei.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($admin)->postJson('/smart-import-absen/preview', [
            'file_absen' => $file,
            'shift_id' => $shift->id,
        ]);

        @unlink($path);

        $response->assertOk()
            ->assertJsonPath('preview.0.source_format', 'machine_package')
            ->assertJsonPath('preview.0.jam_absen', '07:57')
            ->assertJsonPath('preview.0.jam_pulang', '17:01')
            ->assertJsonPath('preview.1.status_absen', 'Libur')
            ->assertJsonPath('preview.2.status_absen', 'Izin Masuk')
            ->assertJsonPath('preview.2.target_table', 'mapping_shifts,dinas_luars');

        $importRows = collect($response->json('preview'))
            ->where('valid', true)
            ->values()
            ->all();

        $this->actingAs($admin)->postJson('/smart-import-absen/import', [
            'import_rows' => $importRows,
        ])->assertOk()
            ->assertJsonPath('stats.created', 3)
            ->assertJsonPath('stats.dinas_created', 1);

        $this->assertDatabaseHas('mapping_shifts', [
            'user_id' => $employee->id,
            'tanggal' => '2026-05-01',
            'jam_absen' => '07:57',
            'jam_pulang' => '17:01',
            'status_absen' => 'Masuk',
        ]);
        $this->assertDatabaseHas('mapping_shifts', [
            'user_id' => $employee->id,
            'tanggal' => '2026-05-02',
            'status_absen' => 'Libur',
        ]);
        $this->assertDatabaseHas('mapping_shifts', [
            'user_id' => $employee->id,
            'tanggal' => '2026-05-03',
            'status_absen' => 'Izin Masuk',
        ]);
        $this->assertSame(1, dinasLuar::where('user_id', $employee->id)->where('tanggal', '2026-05-03')->count());
    }

    private function makeAttendanceAnalysisXlsx(): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray([
            ['Analisa Kehadiran'],
            ['Tanggal Statistik:2026-06-01~2026-06-02'],
            ['User ID.', 'Nama', 'Departemen', 'Hari Kehadiran (Standar/Aktual)', 'Tidak hadir (hari)', 'Cuti (hari)'],
            ['1', 'mgs ilham zuhdi', 'it', '2/0', '2', '0'],
        ]);

        $path = tempnam(sys_get_temp_dir(), 'smart_import_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    private function makeMachineCatatanXlsx(): string
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['Catatan Kehadiran Karyawan'],
            ['Tanggal Kehadiran:2026-05-01~2026-05-02'],
            ['', '', '', '', 'User ID.：', '1', '', '', 'Nama：', 'mgs ilham zuhdi', '', '', 'Departemen：', 'it'],
            ['1', '2'],
            ['', "07:57\n17:00"],
            ['', '17:01'],
        ]);

        $path = tempnam(sys_get_temp_dir(), 'smart_catatan_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    private function makeMachineAbnormalXlsx(): string
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['Kehadiran Tidak Normal'],
            ['Tanggal Kehadiran:2026-05-01~2026-05-02'],
            ['User ID.', 'Nama', 'Departemen', 'Tanggal', 'Pagi', 'Siang', 'Lembur', 'Keterangan', 'Terlambat Masuk', 'Keluar Lebih Awal'],
            ['1', 'mgs ilham zuhdi', 'it', '2026-05-01', 'Tidak hadir', '', '', '', '0', '0'],
        ]);

        $path = tempnam(sys_get_temp_dir(), 'smart_abnormal_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }

    private function makeUniversalAttendanceWorkbookXlsx(): string
    {
        $spreadsheet = new Spreadsheet();

        $schedule = $spreadsheet->getActiveSheet();
        $schedule->setTitle('Pengaturan Shift');
        $schedule->fromArray([
            ['Pengaturan Shift Kehadiran Karyawan (Laporan)'],
            ['Tanggal Statistik:2026-05-01~2026-05-03', 'Situasi Khusus: 25-Perjalanan Bisnis, 26-Cuti, Kosong-Libur'],
            ['User ID.', 'Nama', 'Departemen', '1', '2', '3'],
            ['', '', '', 'Jum', 'Sab', 'Min'],
            ['1', 'mgs ilham zuhdi', 'it', '1', '', '25'],
        ]);

        $summary = $spreadsheet->createSheet();
        $summary->setTitle('Analisa Kehadiran');
        $summary->fromArray([
            ['Analisa Kehadiran'],
            ['Tanggal Statistik:2026-05-01~2026-05-03'],
            ['User ID.', 'Nama', 'Departemen', 'Hari Kehadiran (Standar/Aktual)', 'Tidak hadir (hari)', 'Cuti (hari)'],
            ['1', 'mgs ilham zuhdi', 'it', '2/1', '0', '0'],
        ]);

        $attendance = $spreadsheet->createSheet();
        $attendance->setTitle('Catatan 1');
        $attendance->fromArray([
            ['', '', '', '', '', '', '', '', '', '', '', 'Catatan Kehadiran Karyawan'],
            ['', '', '', '', '', '', '', '', '', '', '', '', '', 'Tanggal Kehadiran:2026-05-01~2026-05-03'],
            ['Dept', 'it', '', '', '', '', '', '', 'Nama', 'mgs ilham zuhdi'],
            ['Tanggal', '2026-05-01~2026-05-03', '', '', '', '', '', '', 'User ID', '1'],
            ['Tidak Hadir (hari)', 'Cuti (hari)', 'Perjalanan Bisnis (hari)', '', 'Kerja (Hari)'],
            [''],
            ['Catatan Kehadiran'],
            ['Tanggal/Minggu', 'Pagi', '', '', '', '', 'Siang', '', '', '', 'Lembur'],
            ['', 'Jam Masuk', '', 'Jam Keluar', '', '', 'Jam Masuk', '', 'Jam Keluar'],
            ['01 Jum', '0.33125', '', '0.70903'],
            ['02 Sab', '', '', ''],
            ['03 Min', '', '', ''],
        ]);

        $spreadsheet->setActiveSheetIndex(1);

        $path = tempnam(sys_get_temp_dir(), 'smart_universal_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        return $path;
    }
}
