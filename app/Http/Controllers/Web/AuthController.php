<?php

namespace App\Http\Controllers\Web;

use App\Models\Identity\User;
use App\Services\Auth\KedatonOAuthService;
use App\Services\Auth\SsoAuthService;
use App\Services\Identity\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AuthController
{
    public function __construct(
        private readonly UserService $userService,
        private readonly KedatonOAuthService $kedaton,
        private readonly SsoAuthService $prestige,
    )
    {
    }

    public function index()
    {
        return \Inertia\Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'captcha' => ['required', 'captcha'],
        ]);
        unset($credentials['captcha']);

        $identifier = trim($credentials['email']);
        if (hash_equals((string) config('services.sso.admin_username', 'elearning'), $identifier)) {
            try {
                $user = $this->prestige->loginPrestigeSuperAdmin($identifier, $credentials['password']);
            } catch (\Throwable $exception) {
                Log::warning('Prestige super admin login failed.', ['exception' => $exception::class]);
                return back()->withErrors(['email_or_username' => 'Kredensial Prestige tidak valid atau layanan sedang tidak tersedia.'])->onlyInput('email');
            }

            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            return to_route('admin.dashboard');
        }

        if (! filter_var($identifier, FILTER_VALIDATE_EMAIL)
            || ! Auth::attempt(['email' => $identifier, 'password' => $credentials['password']], $request->boolean('remember'))) {
            return back()->withErrors(['email_or_username' => 'Email/username atau password tidak sesuai.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = $request->user();

        if (! $user instanceof User
            || ! $user->usesLocalAuthentication()
            || ! $user->hasAnyRole(['super_admin', 'administrator', 'trainer', 'participant'])) {
            Auth::logout();

            return back()->withErrors([
                'email_or_username' => 'Akun ini wajib masuk melalui SSO Kedaton.',
            ])->onlyInput('email');
        }

        return to_route($user->hasRole('participant') && ! $user->hasAnyRole(['super_admin', 'administrator', 'trainer'])
            ? 'participant.dashboard'
            : 'admin.dashboard');
    }

    public function redirectToKedaton(Request $request)
    {
        if (! $this->kedaton->isConfigured()) {
            return to_route('sign-in.index')->withErrors([
                'email_or_username' => 'Layanan login Kedaton belum dikonfigurasi.',
            ]);
        }

        $state = $this->kedaton->newState();
        $request->session()->put('kedaton_oauth_state', $state);

        return redirect()->away($this->kedaton->authorizationUrl($state));
    }

    public function handleKedatonCallback(Request $request)
    {
        $state = (string) $request->session()->pull('kedaton_oauth_state', '');

        if ($request->filled('error') || $state === '' || ! hash_equals($state, (string) $request->query('state'))) {
            return $this->invalidCredentialsResponse();
        }

        $code = $request->query('code');
        if (! is_string($code) || $code === '') {
            return $this->invalidCredentialsResponse();
        }

        try {
            $tokenResponse = $this->kedaton->exchangeCode($code);
            $accessToken = $tokenResponse->json('access_token');

            if (! $tokenResponse->successful() || ! is_string($accessToken) || $accessToken === '') {
                return $this->invalidCredentialsResponse();
            }

            $checkResponse = $this->kedaton->checkToken($accessToken);
            $verifiedData = $checkResponse->json();
            if (! $checkResponse->successful() || ! is_array($verifiedData)) {
                return $this->invalidCredentialsResponse();
            }

            $nip = $this->kedaton->verifiedNip($verifiedData);
            if ($nip === null) {
                Log::warning('Kedaton token check did not include a verified NIP.');
                return $this->invalidCredentialsResponse();
            }

            $employeeResponse = $this->kedaton->getEmployee($nip);
            $employeeData = $employeeResponse->json();
            if (! $employeeResponse->successful() || ! is_array($employeeData)) {
                return $this->invalidCredentialsResponse();
            }

            $user = $this->provisionOrSyncKedatonUser($nip, $employeeData + $verifiedData);
            if (! $user) {
                return $this->invalidCredentialsResponse();
            }

            if (! $user->hasAnyRole(['administrator', 'trainer', 'participant'])) {
                $user->assignRole('participant');
            }

            Auth::login($user);
            $request->session()->regenerate();

            return to_route($user->hasAnyRole(['administrator', 'trainer']) ? 'admin.dashboard' : 'participant.dashboard');
        } catch (\Throwable $exception) {
            Log::warning('Kedaton OAuth authentication failed.', [
                'exception' => $exception::class,
            ]);

            return $this->invalidCredentialsResponse();
        }
    }

    /**
     * NIP is the primary immutable SSO identity. A Kedaton-verified email can
     * link a pre-existing local Admin during migration when its NIP is absent.
     */
    private function provisionOrSyncKedatonUser(string $nip, array $data): ?User
    {
        $userMeta = find_user_by_nip($nip);

        if ($userMeta?->user) {
            $user = $userMeta->user;

            if (! $user->usesSsoAuthentication()) {
                Log::warning('Kedaton SSO attempted to link a local account by NIP.', [
                    'user_id' => $user->id,
                ]);

                return null;
            }

            if (User::where('nip', $nip)->where('id', '!=', $user->id)->exists()) {
                Log::warning('Kedaton SSO found conflicting accounts for a verified NIP.', [
                    'user_id' => $user->id,
                ]);

                return null;
            }

            return $this->syncKedatonUser($user, $nip, $data);
        }

        $userByNip = User::where('nip', $nip)->first();
        if ($userByNip) {
            if (! $userByNip->usesSsoAuthentication()) {
                Log::warning('Kedaton SSO attempted to link a local account by NIP column.', [
                    'user_id' => $userByNip->id,
                ]);

                return null;
            }

            return $this->syncKedatonUser($userByNip, $nip, $data);
        }

        $email = trim((string) ($data['fv_email'] ?? ''));
        if ($email === '') {
            $email = $nip . '@kedaton.invalid';
        }

        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            if (! $existingUser->usesSsoAuthentication()
                || (filled($existingUser->nip) && $existingUser->nip !== $nip)) {
                Log::warning('Kedaton SSO attempted to link a local or mismatched account by email.', [
                    'user_id' => $existingUser->id,
                ]);

                return null;
            }

            return $this->syncKedatonUser($existingUser, $nip, $data);
        }

        return DB::transaction(function () use ($data, $nip, $email) {
            $user = User::create([
                'name' => $data['fv_nama'] ?? 'Peserta Kedaton',
                'email' => $email,
                'nip' => $nip,
                'auth_source' => User::AUTH_SOURCE_SSO,
                // Authentication is exclusively owned by Kedaton. This random
                // value only satisfies the local user schema.
                'password' => Str::password(64),
            ]);

            $user->assignRole('participant');
            $this->userService->storeUserMeta($user->id, $data, $nip);

            return $user;
        });
    }

    /**
     * Synchronize data from the provider only after the account has been
     * confirmed as an SSO identity. Local accounts are never mutated here.
     */
    private function syncKedatonUser(User $user, string $nip, array $data): User
    {
        return DB::transaction(function () use ($user, $data, $nip) {
            $attributes = [
                'name' => $data['fv_nama'] ?? $user->name,
                'nip' => $nip,
            ];

            $email = trim((string) ($data['fv_email'] ?? ''));
            if ($email !== '' && ! User::where('email', $email)->where('id', '!=', $user->id)->exists()) {
                $attributes['email'] = $email;
            }

            $user->update($attributes);
            $this->userService->storeUserMeta($user->id, $data, $nip);

            return $user->fresh();
        });
    }

    private function invalidCredentialsResponse()
    {
        return to_route('sign-in.index')->withErrors([
            'email_or_username' => 'Kredensial tidak valid atau layanan autentikasi sedang tidak tersedia.',
        ]);
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        // Clear legacy SSO credentials when present. Kedaton's OAuth flow does
        // not persist its token, but older local accounts may still have these
        // encrypted fields from the previous integration.
        if ($user instanceof User && ! $user->usesPrestigeAuthentication()) {
            $user->forceFill([
                'access_token' => null,
                'refresh_token' => null,
                'token_expires_at' => null,
            ])->save();
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('sign-in.index');
    }
}
