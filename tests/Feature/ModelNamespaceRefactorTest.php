<?php

namespace Tests\Feature;

use App\Models\Identity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ModelNamespaceRefactorTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_factory_and_auth_provider_use_the_domain_namespace(): void
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(User::class, config('auth.providers.users.model'));
    }

    public function test_legacy_user_morph_types_are_migrated_and_permissions_remain_readable(): void
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'Namespace Test Role', 'guard_name' => 'web']);
        $permission = Permission::create(['name' => 'namespace-test', 'guard_name' => 'web']);

        $user->assignRole($role);
        $user->givePermissionTo($permission);
        $token = $user->createToken('namespace-test');

        DB::table('model_has_roles')
            ->where('model_id', $user->getKey())
            ->update(['model_type' => 'App\\Models\\User']);
        DB::table('model_has_permissions')
            ->where('model_id', $user->getKey())
            ->update(['model_type' => 'App\\Models\\User']);
        DB::table('personal_access_tokens')
            ->where('id', $token->accessToken->getKey())
            ->update(['tokenable_type' => 'App\\Models\\User']);

        $migration = require database_path('migrations/2026_09_12_000000_update_user_model_namespace.php');
        $migration->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $freshUser = $user->fresh();

        $this->assertTrue($freshUser->hasRole($role));
        $this->assertTrue($freshUser->hasPermissionTo($permission));
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $token->accessToken->getKey(),
            'tokenable_type' => User::class,
        ]);
    }
}
