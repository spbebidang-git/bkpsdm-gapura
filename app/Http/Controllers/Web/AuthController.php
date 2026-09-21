<?php

namespace App\Http\Controllers\Web;

use App\Models\Identity\User;
use App\Services\Auth\KedatonOAuthService;
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
    )
    {
    }

    public function index()
    {
        return \Inertia\Inertia::render('Auth/Login');
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

            Auth::login($user);
            $request->session()->regenerate();

            return to_route($user->hasRole('Admin') ? 'admin.dashboard' : 'home');
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

            return DB::transaction(function () use ($user, $data, $nip) {
                $user->update(['name' => $data['fv_nama'] ?? $user->name]);
                $this->userService->storeUserMeta($user->id, $data, $nip);

                return $user->fresh();
            });
        }

        $email = trim((string) ($data['fv_email'] ?? ''));
        if ($email === '') {
            $email = $nip . '@kedaton.invalid';
        }

        $existingUser = User::where('email', $email)->first();
        if ($existingUser) {
            return DB::transaction(function () use ($existingUser, $data, $nip) {
                $existingUser->update(['name' => $data['fv_nama'] ?? $existingUser->name]);
                $this->userService->storeUserMeta($existingUser->id, $data, $nip);

                return $existingUser->fresh();
            });
        }

        return DB::transaction(function () use ($data, $nip, $email) {
            $user = User::create([
                'name' => $data['fv_nama'] ?? 'Peserta Kedaton',
                'email' => $email,
                // Authentication is exclusively owned by Kedaton. This random
                // value only satisfies the local user schema.
                'password' => Str::password(64),
            ]);

            $user->assignRole('Participant');
            $this->userService->storeUserMeta($user->id, $data, $nip);

            return $user;
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
        if ($user instanceof User) {
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
