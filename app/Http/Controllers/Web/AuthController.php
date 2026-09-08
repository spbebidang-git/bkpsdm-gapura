<?php

namespace App\Http\Controllers\Web;

use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AuthController
{
    public function __construct(private readonly UserService $userService)
    {
    }

    public function index()
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Admins authenticate with a locally managed account. Participants are
     * authenticated by Prestige on every sign-in and can never log in locally.
     */
    public function store(Request $request)
    {
        $rules = [
            'username' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:4096'],
            'captcha' => config('captcha.disable')
                ? ['required', 'string']
                : ['required', 'string', 'captcha_api:' . $request->input('captcha_key') . ',default'],
        ];

        if (! config('captcha.disable')) {
            $rules['captcha_key'] = ['required', 'string'];
        }

        $validator = Validator::make($request->all(), $rules, [
            'captcha.captcha_api' => 'The CAPTCHA is incorrect. Please try again.',
        ]);

        if ($validator->fails()) {
            return to_route('sign-in.index')->withErrors($validator)->withInput();
        }

        $identity = trim((string) $request->input('username'));

        if (filter_var($identity, FILTER_VALIDATE_EMAIL)) {
            return $this->loginAdmin($request, Str::lower($identity));
        }

        return $this->loginParticipantWithPrestige($request, $identity);
    }

    private function loginAdmin(Request $request, string $email)
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! $user->hasRole('Admin') || $user->hasRole('Participant') || ! Auth::attempt([
            'email' => $email,
            'password' => $request->input('password'),
        ])) {
            return $this->invalidCredentialsResponse();
        }

        $request->session()->regenerate();

        return Inertia::location(route('admin.dashboard'));
    }

    private function loginParticipantWithPrestige(Request $request, string $nip)
    {
        if (! preg_match('/^\d{3,32}$/', $nip)) {
            return $this->invalidCredentialsResponse();
        }

        try {
            $tokenResponse = $this->requestPrestigeToken($nip, $request->input('password'));

            if (! $tokenResponse->successful() || ! filled($tokenResponse->json('access_token'))) {
                return $this->invalidCredentialsResponse();
            }

            $participantResponse = Http::timeout(5)
                ->acceptJson()
                ->withHeaders([
                    'apikey' => config('services.sso.api_key'),
                    'Authorization' => 'Bearer ' . $tokenResponse->json('access_token'),
                ])
                ->post(config('services.sso.base_url') . '/api/get_nip_data');

            if (! $participantResponse->successful() || ! is_array($participantResponse->json())) {
                return $this->invalidCredentialsResponse();
            }

            $participant = $this->provisionOrSyncParticipant($nip, $participantResponse->json());

            if (! $participant) {
                return $this->invalidCredentialsResponse();
            }

            Auth::login($participant);
            $request->session()->regenerate();

            return to_route('home');
        } catch (\Throwable $exception) {
            // Never write passwords, OAuth responses, tokens, NIP, or PII to logs.
            Log::warning('Prestige participant authentication failed.', [
                'exception' => $exception::class,
            ]);

            return $this->invalidCredentialsResponse();
        }
    }

    private function requestPrestigeToken(string $nip, string $password)
    {
        return Http::timeout(5)
            ->acceptJson()
            ->retry(2, 100)
            ->withHeaders(['apikey' => config('services.sso.api_key')])
            ->post(config('services.sso.base_url') . '/oauth/token', [
                'username' => $nip,
                'password' => $password,
                'grant_type' => 'password',
                'client_id' => config('services.sso.client_id'),
                'client_secret' => config('services.sso.client_secret'),
            ]);
    }

    /**
     * NIP is the immutable SSO identity. Email may change and must never be
     * used to attach an SSO identity to an existing account.
     */
    private function provisionOrSyncParticipant(string $nip, array $data): ?User
    {
        $userMeta = find_user_by_nip($nip);

        if ($userMeta?->user) {
            $user = $userMeta->user;

            if (! $user->hasRole('Participant') || $user->hasRole('Admin')) {
                return null;
            }

            return DB::transaction(function () use ($user, $data, $nip) {
                $user->update(['name' => $data['fv_nama'] ?? $user->name]);
                $this->userService->storeUserMeta($user->id, $data, $nip);

                return $user->fresh();
            });
        }

        $email = trim((string) ($data['fv_email'] ?? ''));
        if ($email === '') {
            $email = $nip . '@prestige.invalid';
        }

        // Do not recover an account from an email match. Doing so could turn a
        // Prestige participant into an Admin if two systems share an email.
        if (User::where('email', $email)->exists()) {
            return null;
        }

        return DB::transaction(function () use ($data, $nip, $email) {
            $user = User::create([
                'name' => $data['fv_nama'] ?? 'Peserta Prestige',
                'email' => $email,
                // Participants cannot authenticate locally. This random value
                // only satisfies the local user schema.
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
            'email_or_username' => 'Kredensial tidak valid atau layanan Prestige sedang tidak tersedia.',
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('sign-in.index');
    }
}
