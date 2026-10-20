<?php

namespace Tests\Feature;

use App\Models\Identity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Inertia\Testing\AssertableInertia as Assert;

class TrainerMissingOpdRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_trainer_without_opd_is_redirected_to_dashboard_with_danger_alert(): void
    {
        $role = Role::findOrCreate('trainer', 'web');
        $role->givePermissionTo(Permission::findOrCreate('dashboard.view', 'web'));
        $trainer = User::factory()->create(['opd_id' => null]);
        $trainer->assignRole($role);

        $this->actingAs($trainer)->get(route('admin.category.index'))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('danger');

        $this->actingAs($trainer)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('flash.danger', 'Akun trainer Anda belum memiliki OPD. Hubungi administrator untuk melengkapi data OPD.')
                ->etc());
    }
}
