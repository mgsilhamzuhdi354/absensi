<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\PegawaiKeluar;
use App\Models\Payroll;
use App\Models\settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ActiveEmployeeSeparationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $activeEmployee;
    private User $exitedEmployee;

    protected function setUp(): void
    {
        parent::setUp();

        settings::create([
            'name' => 'Absensi',
            'logo' => 'logo/absensi.png',
        ]);

        $jabatan = Jabatan::create([
            'nama_jabatan' => 'Operasional',
        ]);

        $this->admin = User::create([
            'name' => 'Admin Pemisahan',
            'email' => 'admin-pemisahan@example.test',
            'username' => 'admin-pemisahan',
            'password' => Hash::make('password'),
            'is_admin' => 'admin',
            'jabatan_id' => $jabatan->id,
        ]);

        $this->activeEmployee = User::create([
            'name' => 'Pegawai Aktif Pengujian',
            'email' => 'aktif-pemisahan@example.test',
            'username' => 'aktif-pemisahan',
            'password' => Hash::make('password'),
            'is_admin' => 'user',
            'jabatan_id' => $jabatan->id,
            'face_descriptor' => str_repeat('1', 150),
        ]);

        $this->exitedEmployee = User::create([
            'name' => 'Pegawai PHK Pengujian',
            'email' => 'phk-pemisahan@example.test',
            'username' => 'phk-pemisahan',
            'password' => Hash::make('password'),
            'is_admin' => 'user',
            'jabatan_id' => $jabatan->id,
            'face_descriptor' => str_repeat('2', 150),
        ]);

        PegawaiKeluar::create([
            'user_id' => $this->exitedEmployee->id,
            'jenis' => 'PHK',
            'alasan' => 'Hubungan kerja berakhir.',
            'tanggal' => now()->toDateString(),
            'status' => PegawaiKeluar::STATUS_APPROVED,
            'approved_by' => $this->admin->id,
        ]);
    }

    /** @test */
    public function active_scope_separates_approved_exited_employees(): void
    {
        $this->assertTrue(User::activeEmployment()->whereKey($this->activeEmployee->id)->exists());
        $this->assertFalse(User::activeEmployment()->whereKey($this->exitedEmployee->id)->exists());
        $this->assertTrue(User::exitedEmployment()->whereKey($this->exitedEmployee->id)->exists());
    }

    /** @test */
    public function employee_and_payroll_lists_only_show_active_employees(): void
    {
        $this->actingAs($this->admin)
            ->get('/pegawai')
            ->assertOk()
            ->assertSee($this->activeEmployee->name)
            ->assertDontSee($this->exitedEmployee->name);

        $this->actingAs($this->admin)
            ->get('/payroll/tambah')
            ->assertOk()
            ->assertSee($this->activeEmployee->name)
            ->assertDontSee($this->exitedEmployee->name);
    }

    /** @test */
    public function payroll_creation_rejects_an_approved_exited_employee(): void
    {
        $response = $this->actingAs($this->admin)->post('/payroll/tambah-proses', [
            'user_id' => $this->exitedEmployee->id,
            'status_id' => 1,
            'bulan' => '09',
            'tahun' => '2026',
            'gaji' => '5000000',
        ]);

        $response->assertSessionHasErrors('user_id');
        $this->assertDatabaseMissing('payrolls', [
            'user_id' => $this->exitedEmployee->id,
            'bulan' => '09',
            'tahun' => '2026',
        ]);
    }

    /** @test */
    public function old_payroll_is_kept_in_a_separate_exited_employee_history(): void
    {
        $this->createPayroll($this->activeEmployee, 'PAY-AKTIF');
        $this->createPayroll($this->exitedEmployee, 'PAY-KELUAR');

        $this->actingAs($this->admin)
            ->get('/payroll')
            ->assertOk()
            ->assertSee('PAY-AKTIF')
            ->assertDontSee('PAY-KELUAR');

        $this->actingAs($this->admin)
            ->get('/payroll?pegawai_status=keluar')
            ->assertOk()
            ->assertSee('PAY-KELUAR')
            ->assertDontSee('PAY-AKTIF');
    }

    /** @test */
    public function public_face_attendance_does_not_expose_exited_employee(): void
    {
        $this->get('/face-descriptors-all')
            ->assertOk()
            ->assertJsonFragment(['username' => $this->activeEmployee->username])
            ->assertJsonMissing(['username' => $this->exitedEmployee->username]);

        $this->get('/attendance/face/status/' . $this->exitedEmployee->username)
            ->assertOk()
            ->assertJson(['status' => 'noUser']);
    }

    /** @test */
    public function approved_exited_employee_cannot_log_in_again(): void
    {
        $this->post('/login-proses-user', [
            'username' => $this->exitedEmployee->username,
            'password' => 'password',
        ])->assertRedirect();

        $this->assertGuest();
    }

    private function createPayroll(User $user, string $number): Payroll
    {
        return Payroll::create([
            'user_id' => $user->id,
            'tanggal_mulai' => '2026-09-01',
            'tanggal_akhir' => '2026-09-30',
            'bulan' => '9',
            'tahun' => '2026',
            'persentase_kehadiran' => '100',
            'no_gaji' => $number,
            'gaji_pokok' => 5000000,
            'uang_transport' => 0,
            'total_reimbursement' => 0,
            'jumlah_mangkir' => 0,
            'uang_mangkir' => 0,
            'total_mangkir' => 0,
            'jumlah_lembur' => 0,
            'uang_lembur' => 0,
            'total_lembur' => 0,
            'jumlah_izin' => 0,
            'uang_izin' => 0,
            'total_izin' => 0,
            'bonus_pribadi' => 0,
            'bonus_team' => 0,
            'bonus_jackpot' => 0,
            'jumlah_terlambat' => 0,
            'uang_terlambat' => 0,
            'total_terlambat' => 0,
            'jumlah_kehadiran' => 0,
            'uang_kehadiran' => 0,
            'total_kehadiran' => 0,
            'saldo_kasbon' => 0,
            'bayar_kasbon' => 0,
            'jumlah_thr' => 0,
            'uang_thr' => 0,
            'total_thr' => 0,
            'loss' => 0,
            'total_penjumlahan' => 5000000,
            'total_pengurangan' => 0,
            'grand_total' => 5000000,
        ]);
    }
}
