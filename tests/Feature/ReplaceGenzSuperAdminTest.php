<?php

namespace Tests\Feature;

use App\Models\Identity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReplaceGenzSuperAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_existing_prestige_identity_is_renamed_without_losing_access(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create([
            'email' => 'genz.developt@gmail.com',
            'nip' => 'elearning',
            'auth_source' => User::AUTH_SOURCE_PRESTIGE,
        ]);
        $admin->assignRole('super_admin');

        $this->migration()->up();

        $admin->refresh();
        $this->assertSame('elearning@prestige.local', $admin->email);
        $this->assertSame('eLearning', $admin->name);
        $this->assertTrue($admin->hasRole('super_admin'));
        $this->assertDatabaseMissing('users', ['email' => 'genz.developt@gmail.com']);
    }

    public function test_separate_legacy_account_loses_super_admin_access(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $legacy = User::factory()->create([
            'email' => 'genz.developt@gmail.com',
            'auth_source' => User::AUTH_SOURCE_LOCAL,
        ]);
        $legacy->assignRole('super_admin');
        $elearning = User::factory()->create([
            'email' => 'elearning@prestige.local',
            'nip' => 'elearning',
            'auth_source' => User::AUTH_SOURCE_PRESTIGE,
        ]);

        $this->migration()->up();

        $this->assertTrue($elearning->fresh()->hasRole('super_admin'));
        $this->assertFalse($legacy->fresh()->hasRole('super_admin'));
        $this->assertSoftDeleted($legacy);
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_10_20_000001_replace_genz_super_admin_with_elearning.php');
    }
}
