<?php

namespace Tests\Feature;

use App\Models\Identity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('super_admin', 'web');
        Role::findOrCreate('participant', 'web');
    }

    public function test_prestige_super_admin_can_create_an_api_token(): void
    {
        config([
            'services.sso.base_url' => 'https://prestige.test',
            'services.sso.client_id' => 'client-id',
            'services.sso.client_secret' => 'client-secret',
        ]);
        $admin = User::factory()->create([
            'email' => 'elearning@prestige.local',
            'nip' => 'elearning',
            'auth_source' => User::AUTH_SOURCE_PRESTIGE,
        ]);
        $admin->assignRole('super_admin');
        Http::fake(['*/oauth/token' => Http::response([
            'access_token' => 'prestige-access', 'refresh_token' => 'prestige-refresh', 'expires_in' => 3600,
        ])]);

        $response = $this->postJson('/api/v1/login', [
            'username' => 'elearning',
            'password' => 'prestige-password',
            'device_name' => 'integration-test',
        ]);

        $response->assertOk()->assertJsonPath('user.email', 'elearning@prestige.local');
        $token = $admin->tokens()->firstOrFail();
        $this->assertSame(['*'], $token->abilities);
        $this->assertNull($token->expires_at);
    }

    public function test_participant_cannot_create_an_api_token_with_a_valid_password(): void
    {
        config([
            'services.sso.base_url' => 'https://prestige.test',
            'services.sso.client_id' => 'client-id',
            'services.sso.client_secret' => 'client-secret',
        ]);
        Http::fake([
            '*/oauth/token' => Http::response(['access_token' => 'participant-access'], 200),
            '*/api/get_nip_data' => Http::response([
                'fv_nama' => 'Peserta', 'fv_email' => 'peserta@test.com',
            ], 200),
        ]);

        $this->postJson('/api/v1/login', [
            'username' => 'participant-nip',
            'password' => 'password123',
        ])->assertUnprocessable()->assertJsonValidationErrors('username');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
