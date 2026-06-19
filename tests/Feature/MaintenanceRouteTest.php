<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceRouteTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function reset_route_is_not_available_outside_local_environment()
    {
        $admin = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.test',
            'username' => 'admin',
            'is_admin' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get('/reset')
            ->assertForbidden();
    }

    /** @test */
    public function reset_route_does_not_run_fresh_migration()
    {
        $routes = file_get_contents(base_path('routes/web.php'));

        $this->assertStringNotContainsString('migrate:fresh', $routes);
    }
}
