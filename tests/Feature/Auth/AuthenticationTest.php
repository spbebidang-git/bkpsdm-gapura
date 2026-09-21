<?php

namespace Tests\Feature\Auth;

use App\Models\Identity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_alias_redirects_to_sign_in(): void
    {
        $this->get('/login')->assertRedirect('/sign-in');
    }

    public function test_users_can_logout_and_sso_tokens_cleared(): void
    {
        $user = User::factory()->create([
            'access_token' => 'access-secret',
            'refresh_token' => 'refresh-secret',
            'token_expires_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect(route('sign-in.index'));

        $user->refresh();
        $this->assertNull($user->access_token);
        $this->assertNull($user->refresh_token);
        $this->assertNull($user->token_expires_at);
    }
}
