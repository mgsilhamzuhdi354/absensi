<?php

namespace Tests\Feature;

use App\Models\Atk;
use App\Models\Jabatan;
use App\Models\Lokasi;
use App\Models\Shift;
use App\Models\settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_dashboard_still_renders_when_inventory_bast_tables_are_missing()
    {
        settings::create([
            'name' => 'Absensi',
            'logo' => 'logo/absensi.png',
        ]);

        $lokasi = Lokasi::create([
            'nama_lokasi' => 'Kantor',
        ]);

        $jabatan = Jabatan::create([
            'nama_jabatan' => 'Crew',
        ]);

        $user = User::create([
            'name' => 'Employee One',
            'email' => 'employee@example.test',
            'username' => 'employee',
            'is_admin' => 'user',
            'lokasi_id' => $lokasi->id,
            'jabatan_id' => $jabatan->id,
        ]);

        Schema::dropIfExists('inventory_bast_documents');

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Dashboard');
    }

    /** @test */
    public function admin_dashboard_shows_quick_atk_monitoring()
    {
        settings::create([
            'name' => 'Absensi',
            'logo' => 'logo/absensi.png',
        ]);

        $jabatan = Jabatan::create([
            'nama_jabatan' => 'Admin Office',
        ]);

        $admin = User::create([
            'name' => 'Admin ATK',
            'email' => 'admin-atk-dashboard@example.test',
            'username' => 'admin-atk-dashboard',
            'is_admin' => 'admin',
            'jabatan_id' => $jabatan->id,
        ]);

        Atk::create([
            'kode_atk' => 'ATK/000001',
            'nama_atk' => 'Pulpen Gel',
            'kategori' => 'Alat Tulis',
            'stok' => 3,
            'satuan' => 'Pcs',
            'lokasi' => 'Lemari Admin',
            'active' => 1,
        ]);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Monitoring Cepat ATK')
            ->assertSee('Pulpen Gel')
            ->assertSee('Stok Rendah');
    }

    /** @test */
    public function admin_dashboard_keeps_top_performers_visible_after_incomplete_smart_import()
    {
        settings::create([
            'name' => 'Absensi',
            'logo' => 'logo/absensi.png',
        ]);

        $jabatan = Jabatan::create([
            'nama_jabatan' => 'Admin Office',
        ]);

        $admin = User::create([
            'name' => 'Admin KPI',
            'email' => 'admin-kpi-dashboard@example.test',
            'username' => 'admin-kpi-dashboard',
            'is_admin' => 'admin',
            'jabatan_id' => $jabatan->id,
        ]);

        $employee = User::create([
            'name' => 'Pegawai KPI Import',
            'email' => 'pegawai-kpi-import@example.test',
            'username' => 'pegawai-kpi-import',
            'is_admin' => 'user',
            'jabatan_id' => $jabatan->id,
        ]);

        $shift = Shift::create([
            'nama_shift' => 'Office',
            'jam_masuk' => '08:00:00',
            'jam_keluar' => '17:00:00',
        ]);

        $this->actingAs($admin)->postJson('/smart-import-absen/import', [
            'import_rows' => [[
                'user_id' => $employee->id,
                'shift_id' => $shift->id,
                'tanggal' => date('Y-m-d'),
                'jam_absen' => null,
                'jam_pulang' => null,
                'status_absen' => 'Tidak Masuk',
                'telat' => 0,
                'pulang_cepat' => 0,
            ]],
        ])->assertOk();

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Top 10 Pegawai dengan Kinerja Terbaik')
            ->assertSee('Pegawai KPI Import')
            ->assertDontSee('Belum ada data KPI periode');
    }
}
