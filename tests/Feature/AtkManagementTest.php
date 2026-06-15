<?php

namespace Tests\Feature;

use App\Models\Atk;
use App\Models\Jabatan;
use App\Models\settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class AtkManagementTest extends TestCase
{
    use RefreshDatabase;

    private $admin;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        settings::create([
            'name' => 'Absensi',
            'logo' => 'logo/absensi.png',
        ]);

        $jabatan = Jabatan::create([
            'nama_jabatan' => 'Admin Office',
        ]);

        $this->admin = User::create([
            'name' => 'Admin ATK',
            'username' => 'admin-atk',
            'email' => 'admin-atk@example.test',
            'is_admin' => 'admin',
            'jabatan_id' => $jabatan->id,
        ]);

        $this->user = User::create([
            'name' => 'Regular User',
            'username' => 'regular-user',
            'email' => 'regular-user@example.test',
            'is_admin' => 'user',
            'jabatan_id' => $jabatan->id,
        ]);
    }

    /** @test */
    public function non_admin_cannot_access_atk_management()
    {
        $this->actingAs($this->user)
            ->get('/atk')
            ->assertRedirect('/absen');

        $this->actingAs($this->user)
            ->get('/atk/scan')
            ->assertRedirect('/absen');
    }

    /** @test */
    public function admin_can_manage_atk_stock_data()
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->get('/atk/tambah')
            ->assertOk()
            ->assertSee('ATK/000001');

        $this->assertDatabaseHas('counters', [
            'name' => 'ATK',
            'text' => 'ATK',
            'counter' => 1,
        ]);

        $this->actingAs($this->admin)->post('/atk/store', [
            'kode_atk' => 'ATK/000001',
            'nama_atk' => 'Pulpen Gel',
            'kategori' => 'Alat Tulis',
            'stok' => '25.5',
            'satuan' => 'Pcs',
            'lokasi' => 'Lemari Admin',
            'keterangan' => 'Warna hitam',
            'active' => '1',
        ])->assertRedirect('/atk/1/detail');

        $atk = Atk::first();

        $this->assertNotNull($atk);
        $this->assertSame('Pulpen Gel', $atk->nama_atk);
        $this->assertSame(25.5, $atk->stok);
        $this->assertSame(1, $atk->active);
        $this->assertNotEmpty($atk->qr_token);
        $this->assertNotEmpty($atk->qr_code_value);
        $this->assertNotEmpty($atk->qr_code_image);
        Storage::disk('public')->assertExists($atk->qr_code_image);

        $this->actingAs($this->admin)
            ->get('/atk?search=Pulpen')
            ->assertOk()
            ->assertSee('Pulpen Gel')
            ->assertSee('ATK/000001')
            ->assertSee('Export Excel');

        $this->actingAs($this->admin)
            ->get('/atk/' . $atk->id . '/detail')
            ->assertOk()
            ->assertSee('Detail ATK')
            ->assertSee('QR Code')
            ->assertSee('Pulpen Gel');

        foreach ([$atk->qr_token, $atk->qr_code_value, $atk->kode_atk, url('/atk/' . $atk->id . '/detail')] as $payload) {
            $this->actingAs($this->admin)
                ->getJson('/atk/scan/lookup?code=' . urlencode($payload))
                ->assertOk()
                ->assertJson([
                    'success' => true,
                    'url' => url('/atk/' . $atk->id . '/detail'),
                ]);
        }

        $this->actingAs($this->admin)
            ->get('/atk/scan')
            ->assertOk()
            ->assertSee('Scan ATK');

        $this->actingAs($this->admin)
            ->get('/atk/' . $atk->id . '/qr/print')
            ->assertOk()
            ->assertSee('QR ' . $atk->kode_atk);

        $this->actingAs($this->admin)
            ->get('/atk/' . $atk->id . '/qr/download')
            ->assertOk();

        Excel::fake();

        $this->actingAs($this->admin)
            ->get('/atk/export?search=Pulpen&status=1')
            ->assertOk();

        Excel::assertDownloaded('Report ATK.xlsx');

        $this->actingAs($this->admin)->put('/atk/update/' . $atk->id, [
            'kode_atk' => 'ATK/000001',
            'nama_atk' => 'Pulpen Gel Hitam',
            'kategori' => 'Alat Tulis',
            'stok' => '12',
            'satuan' => 'Pcs',
            'lokasi' => 'Gudang ATK',
            'keterangan' => 'Stok tersisa',
            'active' => '0',
        ])->assertRedirect('/atk/' . $atk->id . '/detail');

        $this->assertDatabaseHas('atks', [
            'id' => $atk->id,
            'nama_atk' => 'Pulpen Gel Hitam',
            'stok' => 12,
            'lokasi' => 'Gudang ATK',
            'active' => 0,
        ]);

        $this->actingAs($this->admin)
            ->delete('/atk/delete/' . $atk->id)
            ->assertRedirect('/atk');

        $this->assertSame(0, Atk::count());
        Storage::disk('public')->assertMissing($atk->qr_code_image);
    }
}
