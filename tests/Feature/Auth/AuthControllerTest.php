<?php

namespace Tests\Feature\Auth;

use App\Models\Identity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::findOrCreate('Admin');
        Role::findOrCreate('Participant');

        config(['captcha.disable' => true]);
    }

    public function test_local_sign_in_endpoint_is_not_available(): void
    {
        $this->post('/sign-in')->assertMethodNotAllowed();
        $this->assertGuest();
    }

    public function test_local_development_bypass_allows_access_to_the_admin_dashboard(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

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
        $this->assertTrue($admin->hasRole('Admin'));
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

        $response->assertRedirect(route('home'));
        $participant = User::where('email', 'kedaton@example.test')->firstOrFail();
        $this->assertAuthenticatedAs($participant);
        $this->assertTrue($participant->hasRole('Participant'));
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
        $admin = User::factory()->create(['email' => 'admin@kedaton.test']);
        $admin->assignRole('Admin');

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
        $this->assertTrue($admin->fresh()->hasRole('Admin'));
        $this->assertSame('98765', get_user_meta($admin->id, 'nip'));
    }
}
