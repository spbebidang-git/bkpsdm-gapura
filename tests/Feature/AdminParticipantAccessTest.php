<?php

namespace Tests\Feature;

use App\Models\Identity\User;
use App\Services\Integration\CertificateSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminParticipantAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_cannot_access_admin_pages_or_actions_even_with_local_bypass_enabled(): void
    {
        Role::findOrCreate('participant', 'web');
        Role::findOrCreate('super_admin', 'web');
        $participant = User::factory()->create();
        $participant->assignRole('participant');
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        app()->detectEnvironment(static fn () => 'local');
        config(['app.dev_admin_user_id' => $admin->id]);
        Http::preventStrayRequests();
        $this->mock(CertificateSyncService::class)->shouldNotReceive('syncPull');

        foreach ([false, true] as $bypass) {
            config(['app.dev_admin_bypass' => $bypass]);
            foreach (['/admin', '/admin/course', '/admin/certificate', '/admin/management/user'] as $url) {
                $this->actingAs($participant)->get($url)->assertRedirect(route('participant.dashboard'));
                $this->assertAuthenticatedAs($participant);
            }
            foreach (['/admin/certificate/sync-push', '/admin/certificate/sync-pull'] as $url) {
                $this->actingAs($participant)->withSession(['_token' => 'admin-access-test'])
                    ->post($url, ['_token' => 'admin-access-test'])->assertRedirect(route('participant.dashboard'));
                $this->assertAuthenticatedAs($participant);
            }
        }
    }

    public function test_local_bypass_does_not_require_or_provision_admin_for_a_signed_in_participant(): void
    {
        Role::findOrCreate('participant', 'web');
        $participant = User::factory()->create();
        $participant->assignRole('participant');
        app()->detectEnvironment(static fn () => 'local');
        config(['app.dev_admin_bypass' => true, 'app.dev_admin_user_id' => 'missing-admin']);

        $this->actingAs($participant)->get('/admin')->assertRedirect(route('participant.dashboard'));
        $this->assertAuthenticatedAs($participant);
        $this->assertSame(1, User::count());
    }

    public function test_super_admin_keeps_their_identity_with_bypass_enabled(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $other = User::factory()->create();
        $other->assignRole('super_admin');
        app()->detectEnvironment(static fn () => 'local');
        config(['app.dev_admin_bypass' => true, 'app.dev_admin_user_id' => $other->id]);

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->assertAuthenticatedAs($admin);
    }
}
