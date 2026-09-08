<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'Participant']);
    }

    public function test_admin_api_token_has_an_expiry_and_minimal_ability(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
        ]);
        $admin->assignRole('Admin');

        $response = $this->postJson('/api/v1/login', [
            'email' => 'admin@test.com',
            'password' => 'password123',
            'device_name' => 'integration-test',
        ]);

        $response->assertOk()->assertJsonPath('user.email', 'admin@test.com');
        $token = $admin->tokens()->firstOrFail();
        $this->assertSame(['api:read'], $token->abilities);
        $this->assertNotNull($token->expires_at);
        $this->assertTrue($token->expires_at->isFuture());
    }

    public function test_participant_cannot_create_an_api_token_with_a_valid_password(): void
    {
        $participant = User::factory()->create([
            'email' => 'peserta@test.com',
            'password' => Hash::make('password123'),
        ]);
        $participant->assignRole('Participant');

        $this->postJson('/api/v1/login', [
            'email' => 'peserta@test.com',
            'password' => 'password123',
        ])->assertUnprocessable()->assertJsonValidationErrors('email');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
