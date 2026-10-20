<?php

namespace Tests\Feature\Auth;

use App\Models\Identity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'administrator', 'trainer', 'participant'] as $role) {
            Role::findOrCreate($role, 'web');
        }

        config(['captcha.disable' => true]);
    }

    public function test_local_sign_in_endpoint_is_not_available(): void
    {
        $this->from(route('sign-in.index'))->post('/sign-in')->assertRedirect(route('sign-in.index'))->assertSessionHasErrors(['email', 'password', 'captcha']);
        $this->assertGuest();
    }

    public function test_local_development_bypass_allows_access_to_the_admin_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        config([
            'app.dev_admin_bypass' => true,
            'app.dev_admin_user_id' => $admin->id,
        ]);
        app()->detectEnvironment(static fn () => 'local');

        $this->get(route('admin.dashboard'))->assertOk();
    }

    public function test_local_development_bypass_provisions_an_admin_on_an_empty_database(): void
    {
        config([
            'app.dev_admin_bypass' => true,
            'app.dev_admin_user_id' => null,
        ]);
        app()->detectEnvironment(static fn () => 'local');

        $this->get(route('admin.dashboard'))->assertOk();

        $admin = User::where('email', 'admin@local.test')->firstOrFail();
        $this->assertTrue($admin->hasRole('super_admin'));
        $this->assertSame(User::AUTH_SOURCE_LOCAL, $admin->auth_source);
    }

    public function test_participant_can_login_via_kedaton_authorization_code_flow(): void
    {
        config([
            'services.kedaton.base_url' => 'https://kedaton.test',
            'services.kedaton.client_id' => 'gapura-client',
            'services.kedaton.client_secret' => 'client-secret',
            'services.kedaton.redirect_uri' => 'https://gapura.test/auth/kedaton/callback',
            'services.kedaton.data_key' => 'data-key',
            'services.kedaton.data_type' => 'pns',
        ]);

        Http::fake([
            '*/oauth/token' => Http::response(['access_token' => 'kedaton-token'], 200),
            '*/api/token/check' => Http::response(['nip' => '12345'], 200),
            '*/api/get_data_api' => Http::response([
                'fv_nama' => 'Peserta Kedaton',
                'fv_email' => 'kedaton@example.test',
                'status_pegawai' => 'PNS',
            ], 200),
        ]);

        $redirect = $this->get(route('kedaton.redirect'));
        $redirect->assertRedirect();

        parse_str(parse_url($redirect->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('gapura-client', $query['client_id']);
        $this->assertNotEmpty($query['state']);

        $response = $this->get(route('kedaton.callback', [
            'code' => 'authorization-code',
            'state' => $query['state'],
        ]));

        $response->assertRedirect(route('participant.dashboard'));
        $participant = User::where('email', 'kedaton@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($participant);
        $this->assertTrue($participant->hasRole('participant'));
        $this->assertSame(User::AUTH_SOURCE_SSO, $participant->auth_source);
        Http::assertSent(fn (ClientRequest $request) => str_ends_with($request->url(), '/api/token/check')
            && $request->hasHeader('Authorization', 'Bearer kedaton-token'));
    }

    public function test_kedaton_callback_rejects_a_mismatched_state_before_calling_the_provider(): void
    {
        config([
            'services.kedaton.base_url' => 'https://kedaton.test',
            'services.kedaton.client_id' => 'gapura-client',
            'services.kedaton.client_secret' => 'client-secret',
            'services.kedaton.redirect_uri' => 'https://gapura.test/auth/kedaton/callback',
            'services.kedaton.data_key' => 'data-key',
        ]);
        Http::fake();

        $this->get(route('kedaton.redirect'));
        $response = $this->get(route('kedaton.callback', [
            'code' => 'authorization-code',
            'state' => 'invalid-state',
        ]));

        $response->assertRedirect(route('sign-in.index'));
        Http::assertNothingSent();
    }

    public function test_kedaton_can_read_a_nip_from_a_wrapped_token_check_payload(): void
    {
        $service = app(\App\Services\Auth\KedatonOAuthService::class);

        $this->assertSame('12345', $service->verifiedNip([
            'data' => ['fc_nipbr' => '12345'],
        ]));
    }

    public function test_existing_admin_is_authenticated_via_kedaton_and_keeps_its_role(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@kedaton.test',
            'nip' => null,
            'auth_source' => User::AUTH_SOURCE_SSO,
        ]);
        $admin->assignRole('administrator');

        config([
            'services.kedaton.base_url' => 'https://kedaton.test',
            'services.kedaton.client_id' => 'gapura-client',
            'services.kedaton.client_secret' => 'client-secret',
            'services.kedaton.redirect_uri' => 'https://gapura.test/auth/kedaton/callback',
            'services.kedaton.data_key' => 'data-key',
        ]);
        Http::fake([
            '*/oauth/token' => Http::response(['access_token' => 'kedaton-token'], 200),
            '*/api/token/check' => Http::response(['nip' => '98765'], 200),
            '*/api/get_data_api' => Http::response([
                'fv_nama' => 'Admin Kedaton',
                'fv_email' => 'admin@kedaton.test',
            ], 200),
        ]);

        $redirect = $this->get(route('kedaton.redirect'));
        parse_str(parse_url($redirect->headers->get('Location'), PHP_URL_QUERY), $query);

        $response = $this->get(route('kedaton.callback', [
            'code' => 'authorization-code',
            'state' => $query['state'],
        ]));

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
        $this->assertTrue($admin->fresh()->hasRole('administrator'));
        $this->assertSame('98765', get_user_meta($admin->id, 'nip'));
    }

    public function test_local_administrator_can_login_with_gapura_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@local.test',
            'password' => Hash::make('password-local'),
            'auth_source' => User::AUTH_SOURCE_LOCAL,
        ]);
        $user->assignRole('administrator');

        $response = $this->post(route('sign-in.store'), [
            'email' => $user->email,
            'password' => 'password-local',
            'captcha' => 'anything',
        ]);

        $response->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_prestige_super_admin_can_login_with_elearning_username(): void
    {
        config([
            'services.sso.base_url' => 'https://prestige.test',
            'services.sso.client_id' => 'client-id',
            'services.sso.client_secret' => 'client-secret',
            'services.sso.admin_username' => 'elearning',
        ]);
        $admin = User::factory()->create([
            'email' => 'elearning@prestige.local',
            'nip' => 'elearning',
            'auth_source' => User::AUTH_SOURCE_PRESTIGE,
        ]);
        $admin->assignRole('super_admin');
        Http::fake(['*/oauth/token' => Http::response([
            'access_token' => 'prestige-access',
            'refresh_token' => 'prestige-refresh',
            'expires_in' => 3600,
        ])]);

        $this->post(route('sign-in.store'), [
            'email' => 'elearning',
            'password' => 'prestige-password',
            'captcha' => 'anything',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
        $this->assertSame('prestige-access', $admin->fresh()->access_token);
        Http::assertSent(fn (ClientRequest $request) => str_ends_with($request->url(), '/oauth/token')
            && $request['username'] === 'elearning'
            && $request['password'] === 'prestige-password');
    }

    public function test_failed_prestige_login_does_not_create_a_session(): void
    {
        config([
            'services.sso.base_url' => 'https://prestige.test',
            'services.sso.client_id' => 'client-id',
            'services.sso.client_secret' => 'client-secret',
        ]);
        Http::fake(['*/oauth/token' => Http::response(['error' => 'invalid_grant'], 401)]);

        $this->from(route('sign-in.index'))->post(route('sign-in.store'), [
            'email' => 'elearning',
            'password' => 'wrong-password',
            'captcha' => 'anything',
        ])->assertRedirect(route('sign-in.index'))->assertSessionHasErrors('email_or_username');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['nip' => 'elearning']);
    }

    public function test_sso_account_is_rejected_by_local_login(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@sso.test',
            'password' => Hash::make('password-sso'),
            'auth_source' => User::AUTH_SOURCE_SSO,
        ]);
        $user->assignRole('administrator');

        $response = $this->from(route('sign-in.index'))->post(route('sign-in.store'), [
            'email' => $user->email,
            'password' => 'password-sso',
            'captcha' => 'anything',
        ]);

        $response->assertRedirect(route('sign-in.index'));
        $response->assertSessionHasErrors('email_or_username');
        $this->assertGuest();
    }

    public function test_kedaton_callback_never_links_a_local_account(): void
    {
        $localUser = User::factory()->create([
            'email' => 'local@kedaton.test',
            'nip' => '55555',
            'auth_source' => User::AUTH_SOURCE_LOCAL,
        ]);
        $localUser->assignRole('administrator');

        config([
            'services.kedaton.base_url' => 'https://kedaton.test',
            'services.kedaton.client_id' => 'gapura-client',
            'services.kedaton.client_secret' => 'client-secret',
            'services.kedaton.redirect_uri' => 'https://gapura.test/auth/kedaton/callback',
            'services.kedaton.data_key' => 'data-key',
        ]);
        Http::fake([
            '*/oauth/token' => Http::response(['access_token' => 'kedaton-token'], 200),
            '*/api/token/check' => Http::response(['nip' => '55555'], 200),
            '*/api/get_data_api' => Http::response([
                'fv_nama' => 'Tidak Boleh Ditautkan',
                'fv_email' => 'local@kedaton.test',
            ], 200),
        ]);

        $redirect = $this->get(route('kedaton.redirect'));
        parse_str(parse_url($redirect->headers->get('Location'), PHP_URL_QUERY), $query);

        $response = $this->get(route('kedaton.callback', [
            'code' => 'authorization-code',
            'state' => $query['state'],
        ]));

        $response->assertRedirect(route('sign-in.index'));
        $this->assertGuest();
        $this->assertSame('local@kedaton.test', $localUser->fresh()->email);
    }
}
