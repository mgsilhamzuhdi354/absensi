<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\EmployeeCompanyTransfer;
use App\Models\Jabatan;
use App\Models\Lokasi;
use App\Models\Payroll;
use App\Models\settings;
use App\Models\User;
use App\Services\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MultiCompanyAccessTest extends TestCase
{
    use RefreshDatabase;

    private Company $ios;
    private Company $krb;

    protected function setUp(): void
    {
        parent::setUp();

        settings::create([
            'name' => 'Absensi',
            'logo' => 'logo/absensi.png',
        ]);

        $this->ios = Company::where('code', 'IOS')->firstOrFail();
        $this->krb = Company::where('code', 'KRB')->firstOrFail();
    }

    public function test_admin_login_uses_selected_company(): void
    {
        $admin = $this->createUser('admin-ios', $this->ios, ['is_admin' => 'admin']);

        $this->post('/login-proses', [
            'username' => $admin->username,
            'password' => 'secret',
            'company_id' => $this->krb->id,
        ])
            ->assertRedirect('/dashboard')
            ->assertSessionHas(CompanyContext::SESSION_KEY, $this->krb->id);
    }

    public function test_regular_user_login_ignores_selected_company(): void
    {
        $user = $this->createUser('user-ios', $this->ios, ['is_admin' => 'user']);

        $this->post('/login-proses', [
            'username' => $user->username,
            'password' => 'secret',
            'company_id' => $this->krb->id,
        ])
            ->assertRedirect('/dashboard')
            ->assertSessionHas(CompanyContext::SESSION_KEY, $this->ios->id);
    }

    public function test_pegawai_list_is_isolated_by_active_company(): void
    {
        $admin = $this->createUser('admin-ios', $this->ios, ['is_admin' => 'admin']);
        $iosUser = $this->createUser('pegawai-ios', $this->ios, ['name' => 'Pegawai IOS']);
        $krbUser = $this->createUser('pegawai-krb', $this->krb, ['name' => 'Pegawai KRB']);

        $this->actingAs($admin)
            ->withSession([CompanyContext::SESSION_KEY => $this->ios->id])
            ->get('/pegawai')
            ->assertOk()
            ->assertSee($iosUser->name)
            ->assertDontSee($krbUser->name);

        $this->actingAs($admin)
            ->withSession([CompanyContext::SESSION_KEY => $this->krb->id])
            ->get('/pegawai')
            ->assertOk()
            ->assertSee($krbUser->name)
            ->assertDontSee($iosUser->name);
    }

    public function test_admin_can_transfer_employee_company_without_moving_old_payroll_history(): void
    {
        $admin = $this->createUser('admin-ios', $this->ios, ['is_admin' => 'admin']);
        $sourceJabatan = Jabatan::withoutGlobalScope('company')->create([
            'company_id' => $this->ios->id,
            'nama_jabatan' => 'Staff IOS',
        ]);
        $sourceLokasi = Lokasi::withoutGlobalScope('company')->create([
            'company_id' => $this->ios->id,
            'nama_lokasi' => 'Kantor IOS',
            'status' => 'approved',
            'keterangan' => 'Office',
        ]);
        $destinationJabatan = Jabatan::withoutGlobalScope('company')->create([
            'company_id' => $this->krb->id,
            'nama_jabatan' => 'Staff KRB',
        ]);
        $destinationLokasi = Lokasi::withoutGlobalScope('company')->create([
            'company_id' => $this->krb->id,
            'nama_lokasi' => 'Kantor KRB',
            'status' => 'approved',
            'keterangan' => 'Office',
        ]);
        $employee = $this->createUser('employee-ios', $this->ios, [
            'name' => 'Employee IOS',
            'jabatan_id' => $sourceJabatan->id,
            'lokasi_id' => $sourceLokasi->id,
        ]);
        $oldPayroll = $this->createPayroll($employee, $this->ios);

        $this->actingAs($admin)
            ->withSession([CompanyContext::SESSION_KEY => $this->ios->id])
            ->post('/pegawai/' . $employee->id . '/transfer-company', [
                'destination_company_id' => $this->krb->id,
                'destination_jabatan_id' => $destinationJabatan->id,
                'destination_lokasi_id' => $destinationLokasi->id,
                'notes' => 'Mutasi operasional',
            ])
            ->assertRedirect('/pegawai');

        $employee->refresh();
        $this->assertSame($this->krb->id, (int) $employee->company_id);
        $this->assertSame($destinationJabatan->id, (int) $employee->jabatan_id);
        $this->assertSame($destinationLokasi->id, (int) $employee->lokasi_id);
        $this->assertSame($this->ios->id, (int) $oldPayroll->fresh()->company_id);

        $this->assertDatabaseHas('employee_company_transfers', [
            'user_id' => $employee->id,
            'source_company_id' => $this->ios->id,
            'destination_company_id' => $this->krb->id,
            'transferred_by' => $admin->id,
        ]);

        $this->actingAs($employee->fresh());
        $newPayroll = $this->createPayroll($employee->fresh());
        $this->assertSame($this->krb->id, (int) $newPayroll->company_id);
    }

    private function createUser(string $username, Company $company, array $overrides = []): User
    {
        return User::create(array_merge([
            'company_id' => $company->id,
            'name' => $username,
            'username' => $username,
            'email' => $username . '@example.test',
            'password' => Hash::make('secret'),
            'is_admin' => 'user',
        ], $overrides));
    }

    private function createPayroll(User $user, ?Company $company = null): Payroll
    {
        return Payroll::create([
            'company_id' => $company?->id,
            'user_id' => $user->id,
            'tanggal_mulai' => '2026-07-01',
            'tanggal_akhir' => '2026-07-25',
            'bulan' => '07',
            'tahun' => '2026',
            'persentase_kehadiran' => '100',
            'no_gaji' => 'GJ-' . uniqid(),
            'gaji_pokok' => 5000000,
            'uang_transport' => 250000,
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
            'total_penjumlahan' => 5250000,
            'total_pengurangan' => 0,
            'grand_total' => 5250000,
        ]);
    }
}
