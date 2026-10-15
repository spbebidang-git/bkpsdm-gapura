<?php

namespace Tests\Feature;

use App\Models\Identity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ManagementUserAuthSourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'administrator', 'trainer', 'participant'] as $role) {
            Role::findOrCreate($role, 'web');
        }
    }

    public function test_super_admin_can_create_a_local_administrator(): void
    {
        $superAdmin = User::factory()->create(['auth_source' => User::AUTH_SOURCE_LOCAL]);
        $superAdmin->assignRole('super_admin');

        $response = $this->actingAs($superAdmin)->post(route('admin.management.user.store'), [
            'name' => 'Admin Lokal',
            'email' => 'admin.local@example.test',
            'password' => 'password-local',
            'password_confirmation' => 'password-local',
            'auth_source' => User::AUTH_SOURCE_LOCAL,
            'roles' => ['administrator'],
        ]);

        $response->assertRedirect(route('admin.management.user.index'));
        $user = User::where('email', 'admin.local@example.test')->firstOrFail();
        $this->assertSame(User::AUTH_SOURCE_LOCAL, $user->auth_source);
        $this->assertTrue($user->hasRole('administrator'));
    }

    public function test_participant_cannot_be_created_from_admin_management(): void
    {
        $superAdmin = User::factory()->create(['auth_source' => User::AUTH_SOURCE_LOCAL]);
        $superAdmin->assignRole('super_admin');

        $response = $this->actingAs($superAdmin)->from(route('admin.management.user.create'))
            ->post(route('admin.management.user.store'), [
                'name' => 'Peserta Lokal',
                'email' => 'participant.local@example.test',
                'password' => 'password-local',
                'password_confirmation' => 'password-local',
                'auth_source' => User::AUTH_SOURCE_LOCAL,
                'roles' => ['participant'],
            ]);

        $response->assertSessionHasErrors('roles.0');
        $this->assertDatabaseMissing('users', ['email' => 'participant.local@example.test']);
    }

    public function test_admin_list_excludes_trainers_and_participants(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $admin = User::factory()->create();
        $admin->assignRole('administrator');
        $participant = User::factory()->create();
        $participant->assignRole('participant');
        $trainer = User::factory()->create();
        $trainer->assignRole('trainer');

        $response = $this->actingAs($superAdmin)->getJson(route('admin.management.user.index'), ['X-Requested-With' => 'XMLHttpRequest']);
        $response->assertOk()->assertJsonCount(2, 'data');
        $this->assertEqualsCanonicalizing([$superAdmin->id, $admin->id], array_column($response->json('data'), 'id'));
        $this->actingAs($superAdmin)->get(route('admin.management.user.edit', $participant))->assertNotFound();
        $this->actingAs($superAdmin)->delete(route('admin.management.user.destroy', $trainer))->assertNotFound();
    }

    public function test_sso_account_only_accepts_role_changes(): void
    {
        $superAdmin = User::factory()->create(['auth_source' => User::AUTH_SOURCE_LOCAL]);
        $superAdmin->assignRole('super_admin');
        $ssoUser = User::factory()->create([
            'name' => 'Nama Kedaton',
            'email' => 'sso.user@example.test',
            'nip' => '199111072019031003',
            'auth_source' => User::AUTH_SOURCE_SSO,
        ]);
        $ssoUser->assignRole(['administrator', 'trainer']);

        $response = $this->actingAs($superAdmin)->put(route('admin.management.user.update', $ssoUser), [
            'name' => 'Tidak Boleh Berubah',
            'email' => 'changed@example.test',
            'password' => 'password-local',
            'password_confirmation' => 'password-local',
            'auth_source' => 'local',
            'roles' => ['administrator'],
        ]);

        $response->assertRedirect(route('admin.management.user.index'));
        $ssoUser->refresh();
        $this->assertSame('Nama Kedaton', $ssoUser->name);
        $this->assertSame('sso.user@example.test', $ssoUser->email);
        $this->assertSame(User::AUTH_SOURCE_SSO, $ssoUser->auth_source);
        $this->assertTrue($ssoUser->hasRole('administrator'));
        $this->assertTrue($ssoUser->hasRole('trainer'));
    }
}
