<?php

namespace Tests\Feature\Auth;

use App\Models\Identity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use App\Services\Auth\TokenService;
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

    public function test_prestige_super_admin_tokens_remain_available_after_logout(): void
    {
        \Spatie\Permission\Models\Role::findOrCreate('super_admin', 'web');
        $user = User::factory()->create([
            'email' => 'elearning@prestige.local',
            'nip' => 'elearning',
            'auth_source' => User::AUTH_SOURCE_PRESTIGE,
            'access_token' => 'prestige-access',
            'refresh_token' => 'prestige-refresh',
            'token_expires_at' => now()->addHour(),
        ]);
        $user->assignRole('super_admin');

        $this->actingAs($user)->post('/logout')->assertRedirect(route('sign-in.index'));

        $this->assertSame('prestige-access', $user->fresh()->access_token);
        $this->assertSame('prestige-refresh', $user->fresh()->refresh_token);
    }

    public function test_integration_token_refresh_uses_prestige_super_admin_even_when_another_admin_is_logged_in(): void
    {
        \Spatie\Permission\Models\Role::findOrCreate('super_admin', 'web');
        \Spatie\Permission\Models\Role::findOrCreate('administrator', 'web');
        config([
            'services.sso.base_url' => 'https://prestige.test',
            'services.sso.client_id' => 'client-id',
            'services.sso.client_secret' => 'client-secret',
        ]);

        $superAdmin = User::factory()->create([
            'email' => 'elearning@prestige.local', 'nip' => 'elearning',
            'auth_source' => User::AUTH_SOURCE_PRESTIGE, 'access_token' => 'expired-token',
            'refresh_token' => 'prestige-refresh', 'token_expires_at' => now()->subMinute(),
        ]);
        $superAdmin->assignRole('super_admin');
        $admin = User::factory()->create(['email' => 'admin@example.test', 'refresh_token' => 'other-refresh']);
        $admin->assignRole('administrator');
        Http::fake(['*/oauth/token' => Http::response([
            'access_token' => 'fresh-prestige-token', 'refresh_token' => 'fresh-refresh', 'expires_in' => 3600,
        ])]);

        $this->actingAs($admin);
        $this->assertSame('Bearer fresh-prestige-token', app(TokenService::class)->getToken());
        $this->assertSame('fresh-prestige-token', $superAdmin->fresh()->access_token);
        Http::assertSent(fn ($request) => $request['grant_type'] === 'refresh_token'
            && $request['refresh_token'] === 'prestige-refresh');
    }
}
