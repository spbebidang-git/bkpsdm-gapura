<?php

namespace Tests\Feature\Auth;

use App\Models\Identity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('administrator', 'web');
    }

    public function test_password_update_endpoint_is_not_exposed(): void
    {
        $user = User::factory()->create();
        $user->assignRole('administrator');

        $this->actingAs($user)->put('/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertNotFound();
    }
}
