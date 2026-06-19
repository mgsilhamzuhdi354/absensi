<?php

namespace Tests\Feature;

use App\Models\Jabatan;
use App\Models\Lokasi;
use App\Models\settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApprovalModalRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

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

        $lokasi = Lokasi::create([
            'nama_lokasi' => 'Kantor Pusat',
            'status' => 'approved',
        ]);

        Role::create(['name' => 'admin']);

        $this->admin = User::create([
            'name' => 'Approval Admin',
            'email' => 'approval-admin@example.test',
            'username' => 'approval-admin',
            'is_admin' => 'admin',
            'jabatan_id' => $jabatan->id,
            'lokasi_id' => $lokasi->id,
        ]);
        $this->admin->assignRole('admin');
    }

    /** @test */
    public function approval_related_pages_render_with_global_interaction_guard()
    {
        foreach ([
            '/exit',
            '/reimbursement',
            '/list-pengajuan-keuangan',
            '/data-lembur',
            '/lokasi-kantor/pending-location',
        ] as $path) {
            $this->actingAs($this->admin)
                ->get($path)
                ->assertOk()
                ->assertSee('window.isUiInteractionLocked', false)
                ->assertSee('appUiSubmitting', false);
        }
    }
}
