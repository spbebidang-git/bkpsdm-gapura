<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Admin']);
        Role::create(['name' => 'Participant']);

        config([
            'services.sso.base_url' => 'https://prestige.test',
            'captcha.disable' => true,
        ]);
    }

    public function test_admin_can_login_with_a_local_email_account(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@test.com',
            'password' => Hash::make('password123'),
        ]);
        $admin->assignRole('Admin');

        $response = $this->post('/sign-in', [
            'username' => 'admin@test.com',
            'password' => 'password123',
            'captcha' => 'test-captcha',
        ], [
            'X-Inertia' => 'true',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);

        $response->assertStatus(409);
        $response->assertHeader('X-Inertia-Location', route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_participant_must_authenticate_with_prestige_even_when_a_local_password_matches(): void
    {
        $participant = User::factory()->create([
            'email' => 'peserta@test.com',
            'password' => Hash::make('password'),
        ]);
        $participant->assignRole('Participant');
        update_user_meta($participant->id, 'nip', '12345');

        Http::fake([
            '*/oauth/token' => Http::response([], 401),
        ]);

        $response = $this->post('/sign-in', [
            'username' => '12345',
            'password' => 'password',
            'captcha' => 'test-captcha',
        ]);

        $response->assertRedirect(route('sign-in.index'));
        $response->assertSessionHasErrors('email_or_username');
        $this->assertGuest();
    }

    public function test_participant_can_login_when_prestige_verifies_the_nip(): void
    {
        $participant = User::factory()->create(['email' => 'peserta@test.com']);
        $participant->assignRole('Participant');
        update_user_meta($participant->id, 'nip', '12345');

        Http::fake([
            '*/oauth/token' => Http::response(['access_token' => 'prestige-token'], 200),
            '*/api/get_nip_data' => Http::response([
                'fv_nama' => 'Peserta Prestige',
                'fv_email' => 'peserta@test.com',
                'status_pegawai' => 'PNS',
            ], 200),
        ]);

        $response = $this->post('/sign-in', [
            'username' => '12345',
            'password' => 'password-dari-prestige',
            'captcha' => 'test-captcha',
        ]);

        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($participant);
        $this->assertSame('Peserta Prestige', $participant->fresh()->name);
        Http::assertSent(fn (ClientRequest $request) => str_ends_with($request->url(), '/oauth/token'));
    }

    public function test_prestige_email_collision_cannot_modify_or_login_an_admin(): void
    {
        $admin = User::factory()->create([
            'email' => 'shared@test.com',
            'password' => Hash::make('admin-password'),
        ]);
        $admin->assignRole('Admin');

        Http::fake([
            '*/oauth/token' => Http::response(['access_token' => 'prestige-token'], 200),
            '*/api/get_nip_data' => Http::response([
                'fv_nama' => 'Peserta Prestige',
                'fv_email' => 'shared@test.com',
            ], 200),
        ]);

        $response = $this->post('/sign-in', [
            'username' => '99999',
            'password' => 'password-dari-prestige',
            'captcha' => 'test-captcha',
        ]);

        $response->assertRedirect(route('sign-in.index'));
        $this->assertGuest();
        $this->assertTrue(Hash::check('admin-password', $admin->fresh()->password));
        $this->assertFalse($admin->fresh()->hasRole('Participant'));
    }

    public function test_new_participant_is_provisioned_without_reusing_the_prestige_password(): void
    {
        Http::fake([
            '*/oauth/token' => Http::response(['access_token' => 'prestige-token'], 200),
            '*/api/get_nip_data' => Http::response([
                'fv_nama' => 'Peserta Baru',
                'fv_email' => 'baru@test.com',
                'status_pegawai' => 'PNS',
            ], 200),
        ]);

        $response = $this->post('/sign-in', [
            'username' => '99999',
            'password' => 'password-dari-prestige',
            'captcha' => 'test-captcha',
        ]);

        $response->assertRedirect(route('home'));
        $participant = User::where('email', 'baru@test.com')->firstOrFail();
        $this->assertTrue($participant->hasRole('Participant'));
        $this->assertFalse(Hash::check('password-dari-prestige', $participant->password));
    }
}
