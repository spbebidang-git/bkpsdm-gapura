<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sign_in_screen_can_be_rendered(): void
    {
        $this->get('/sign-in')->assertOk();
    }

    public function test_legacy_local_login_endpoint_is_not_available(): void
    {
        $this->post('/login', [
            'email' => 'participant@example.test',
            'password' => 'password',
        ])->assertNotFound();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/sign-in');
    }
}
