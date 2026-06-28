<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\Lokasi;
use App\Models\User;
use App\Models\settings;
use App\Services\EmployeeQrService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EmployeeQrIdCardTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function admin_can_open_employee_qr_page_and_images_are_generated()
    {
        Storage::fake('public');
        [$admin, $employee] = $this->seedUsers();

        $this->actingAs($admin)
            ->get('/pegawai/qrcode/' . $employee->id)
            ->assertOk()
            ->assertSee('QR Profil Dynamic')
            ->assertSee('QR Simpan Kontak');

        $employee->refresh();
        $this->assertNotNull($employee->employee_qr_token);
        $this->assertLessThanOrEqual(4, strlen($employee->employee_qr_token));
        $this->assertStringContainsString('/e/', $employee->employee_qr_profile_value);
        $this->assertStringContainsString('/e/' . $employee->employee_qr_token . '/v', $employee->employee_qr_vcard_value);
        $this->assertStringNotContainsString('BEGIN:VCARD', $employee->employee_qr_vcard_value);
        Storage::disk('public')->assertExists($employee->employee_qr_profile_image);
        Storage::disk('public')->assertExists($employee->employee_qr_vcard_image);
    }

    /** @test */
    public function admin_can_manage_custom_scan_information_from_qr_page()
    {
        Storage::fake('public');
        [$admin, $employee] = $this->seedUsers([], ['employee_qr_visible_fields' => json_encode([
            'foto', 'name', 'employee_id', 'jabatan', 'custom_info',
        ])]);

        $this->actingAs($admin)
            ->get('/pegawai/qrcode/' . $employee->id)
            ->assertOk()
            ->assertSee('Informasi yang Tampil Saat Scan')
            ->assertSee('Tambah Informasi')
            ->assertSee('Cari ikon');

        $employee->refresh();

        $this->actingAs($admin)
            ->post('/pegawai/' . $employee->id . '/qr/info', [
                'info_labels' => ['Golongan Darah', 'Area Kerja'],
                'info_values' => ['O+', 'Lantai 3'],
                'info_icons' => ['heart', 'building'],
            ])
            ->assertRedirect();

        $employee->refresh();
        $this->assertSame([
            ['label' => 'Golongan Darah', 'value' => 'O+', 'icon' => 'heart'],
            ['label' => 'Area Kerja', 'value' => 'Lantai 3', 'icon' => 'building'],
        ], json_decode($employee->employee_qr_custom_info, true));

        $this->get('/e/' . $employee->employee_qr_token)
            ->assertOk()
            ->assertSee('Golongan Darah')
            ->assertSee('O+')
            ->assertSee('fa-heart', false)
            ->assertSee('Area Kerja')
            ->assertSee('Lantai 3')
            ->assertSee('fa-building', false);
    }

    /** @test */
    public function custom_scan_information_rejects_icons_outside_catalog()
    {
        Storage::fake('public');
        [$admin, $employee] = $this->seedUsers();

        $this->actingAs($admin)
            ->from('/pegawai/qrcode/' . $employee->id)
            ->post('/pegawai/' . $employee->id . '/qr/info', [
                'info_labels' => ['Kode'],
                'info_values' => ['A1'],
                'info_icons' => ['bad-icon'],
            ])
            ->assertRedirect('/pegawai/qrcode/' . $employee->id)
            ->assertSessionHasErrors('info_icons.0');
    }

    /** @test */
    public function legacy_custom_scan_information_without_icon_uses_default_info_icon()
    {
        Storage::fake('public');
        [, $employee] = $this->seedUsers([
            'employee_qr_custom_info' => json_encode([
                ['label' => 'Area Kerja', 'value' => 'Lt. 3'],
            ]),
        ], ['employee_qr_visible_fields' => json_encode([
            'name', 'custom_info',
        ])]);

        $employee = app(EmployeeQrService::class)->ensure($employee);

        $this->get('/e/' . $employee->employee_qr_token)
            ->assertOk()
            ->assertSee('Area Kerja')
            ->assertSee('Lt. 3')
            ->assertSee('fa-info-circle', false);
    }

    /** @test */
    public function public_profile_is_accessible_without_login_and_hides_sensitive_fields()
    {
        Storage::fake('public');
        [, $employee] = $this->seedUsers([
            'ktp' => '1234567890123456',
            'bpjs_kesehatan' => '9999999999999',
            'rekening' => '8888888888',
            'gaji_pokok' => 7500000,
            'alamat' => 'Jl. Aman No. 1',
            'employee_qr_custom_info' => json_encode([
                ['label' => 'Golongan Darah', 'value' => 'O'],
                ['label' => 'Area Kerja', 'value' => 'Lt. 3'],
            ]),
        ], ['employee_qr_visible_fields' => json_encode([
            'foto', 'name', 'employee_id', 'jabatan', 'lokasi', 'telepon', 'email', 'alamat', 'custom_info',
        ])]);

        $employee = app(EmployeeQrService::class)->ensure($employee);

        $this->get('/e/' . $employee->employee_qr_token)
            ->assertOk()
            ->assertSee($employee->name)
            ->assertSee('EMP-001')
            ->assertSee('Jl. Aman No. 1')
            ->assertSee('Golongan Darah')
            ->assertSee('Area Kerja')
            ->assertDontSee('1234567890123456')
            ->assertDontSee('9999999999999')
            ->assertDontSee('8888888888')
            ->assertDontSee('7500000');
    }

    /** @test */
    public function public_vcard_uses_allowed_contact_fields()
    {
        Storage::fake('public');
        [, $employee] = $this->seedUsers([], ['employee_qr_visible_fields' => json_encode([
            'name', 'jabatan', 'telepon', 'email',
        ])]);

        $employee = app(EmployeeQrService::class)->ensure($employee);

        $response = $this->get('/e/' . $employee->employee_qr_token . '/v');

        $response->assertOk();
        $content = $response->getContent();
        $this->assertStringContainsString('BEGIN:VCARD', $content);
        $this->assertStringContainsString('FN:Employee One', $content);
        $this->assertStringContainsString('TITLE:IT Engineer', $content);
        $this->assertStringContainsString('TEL;TYPE=CELL:08123456789', $content);
        $this->assertStringContainsString('EMAIL;TYPE=WORK:employee@example.test', $content);
    }

    /** @test */
    public function settings_accepts_only_safe_employee_qr_fields()
    {
        [$admin] = $this->seedUsers();

        $this->actingAs($admin)
            ->post('/settings/store', [
                'name' => 'Absensi',
                'employee_qr_visible_fields' => ['name', 'telepon', 'email'],
            ])
            ->assertRedirect();

        $this->assertSame(
            ['name', 'telepon', 'email'],
            json_decode(settings::first()->employee_qr_visible_fields, true)
        );

        $this->actingAs($admin)
            ->from('/settings')
            ->post('/settings/store', [
                'name' => 'Absensi',
                'employee_qr_visible_fields' => ['ktp'],
            ])
            ->assertSessionHasErrors('employee_qr_visible_fields.0');
    }

    /** @test */
    public function admin_can_regenerate_employee_qr_token_and_old_public_url_stops_working()
    {
        Storage::fake('public');
        [$admin, $employee] = $this->seedUsers();
        $employee = app(EmployeeQrService::class)->ensure($employee);
        $oldToken = $employee->employee_qr_token;

        $this->actingAs($admin)
            ->post('/pegawai/' . $employee->id . '/qr/regenerate')
            ->assertRedirect();

        $employee->refresh();
        $this->assertNotSame($oldToken, $employee->employee_qr_token);
        $this->get('/id-card/' . $oldToken)->assertNotFound();
        $this->get('/e/' . $employee->employee_qr_token)->assertOk();
    }

    /** @test */
    public function employee_id_and_emergency_contact_are_saved_from_employee_forms()
    {
        [$admin] = $this->seedUsers();
        $jabatan = Jabatan::first();
        $lokasi = Lokasi::first();

        $this->actingAs($admin)->post('/pegawai/tambah-pegawai-proses', [
            'name' => 'New Employee',
            'employee_id' => 'EMP-NEW',
            'email' => 'newemployee@gmail.com',
            'telepon' => '082222222222',
            'username' => 'new-employee',
            'password' => 'secret123',
            'lokasi_id' => $lokasi->id,
            'tgl_lahir' => '1998-01-01',
            'tgl_join' => '2026-01-01',
            'gender' => 'Laki-Laki',
            'is_admin' => 'user',
            'status_nikah' => 'TK/0',
            'jabatan_id' => $jabatan->id,
            'nama_kontak_darurat' => 'Emergency Person',
            'telepon_kontak_darurat' => '083333333333',
            'hubungan_kontak_darurat' => 'Saudara',
        ])->assertRedirect('/pegawai');

        $employee = User::where('username', 'new-employee')->firstOrFail();
        $this->assertSame('EMP-NEW', $employee->employee_id);
        $this->assertSame('Emergency Person', $employee->nama_kontak_darurat);

        $this->actingAs($admin)->put('/pegawai/proses-edit/' . $employee->id, [
            'name' => 'New Employee Updated',
            'employee_id' => 'EMP-UPDATED',
            'email' => $employee->email,
            'telepon' => '084444444444',
            'username' => $employee->username,
            'lokasi_id' => $lokasi->id,
            'tgl_lahir' => '1998-01-01',
            'tgl_join' => '2026-01-01',
            'gender' => 'Laki-Laki',
            'is_admin' => 'user',
            'status_nikah' => 'TK/0',
            'jabatan_id' => $jabatan->id,
            'nama_kontak_darurat' => 'Emergency Updated',
            'telepon_kontak_darurat' => '085555555555',
            'hubungan_kontak_darurat' => 'Orang Tua',
        ])->assertRedirect('/pegawai');

        $employee->refresh();
        $this->assertSame('EMP-UPDATED', $employee->employee_id);
        $this->assertSame('Emergency Updated', $employee->nama_kontak_darurat);
        $this->assertSame('085555555555', $employee->telepon_kontak_darurat);
    }

    /** @test */
    public function employee_card_and_print_routes_still_render()
    {
        Storage::fake('public');
        [, $employee] = $this->seedUsers();

        $this->actingAs($employee)
            ->get('/kartu-pegawai')
            ->assertOk()
            ->assertSee('Profil')
            ->assertSee('Kontak');

        $this->actingAs($employee)
            ->get('/pegawai/print/' . $employee->id . '?mode=profile')
            ->assertOk();
    }

    private function seedUsers(array $employeeOverride = [], array $settingsOverride = []): array
    {
        settings::create(array_merge([
            'name' => 'Absensi',
            'email' => 'hr@example.test',
            'phone' => '021123456',
        ], $settingsOverride));

        $jabatan = Jabatan::create([
            'nama_jabatan' => 'IT Engineer',
        ]);

        $lokasi = Lokasi::create([
            'nama_lokasi' => 'Kantor Pusat',
            'status' => 'approved',
            'keterangan' => 'Office',
        ]);

        $admin = User::create([
            'name' => 'Admin',
            'username' => 'admin',
            'email' => 'admin@example.test',
            'is_admin' => 'admin',
            'jabatan_id' => $jabatan->id,
            'lokasi_id' => $lokasi->id,
        ]);

        $employee = User::create(array_merge([
            'name' => 'Employee One',
            'employee_id' => 'EMP-001',
            'username' => 'employee-one',
            'email' => 'employee@example.test',
            'telepon' => '08123456789',
            'is_admin' => 'user',
            'jabatan_id' => $jabatan->id,
            'lokasi_id' => $lokasi->id,
            'tgl_lahir' => '1997-01-01',
            'tgl_join' => '2026-01-01',
            'gender' => 'Laki-Laki',
            'status_nikah' => 'TK/0',
        ], $employeeOverride));

        return [$admin, $employee];
    }
}
