<?php

namespace Tests\Feature;

use App\Models\Identity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('Admin');
        Role::findOrCreate('Participant');
    }

    public function test_admin_can_update_the_local_admin_profile(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin)->patch('/profile', [
            'name' => 'Admin Baru',
            'email' => 'admin-baru@example.test',
        ]);

        $response->assertSessionHasNoErrors()->assertRedirect(route('admin.dashboard'));
        $this->assertSame('Admin Baru', $admin->fresh()->name);
    }

    public function test_participant_cannot_change_prestige_owned_profile_fields(): void
    {
        $participant = User::factory()->create();
        $participant->assignRole('Participant');

        $this->actingAs($participant)->patch('/profile', [
            'name' => 'Tidak Boleh',
            'email' => 'tidak-boleh@example.test',
        ])->assertForbidden();
    }
}
