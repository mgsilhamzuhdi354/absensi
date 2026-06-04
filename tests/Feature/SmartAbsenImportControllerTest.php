<?php

namespace Tests\Feature;

use App\Models\MappingShift;
use App\Models\Shift;
use App\Models\User;
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
            ->assertJsonPath('raw_preview.total_columns', 4)
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
    public function preview_accepts_multiple_machine_files()
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
}
