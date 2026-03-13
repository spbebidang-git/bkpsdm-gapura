<?php

namespace App\Http\Controllers\Web;

use App\Models\Management\UserMeta;
use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;
use Mews\Captcha\Facades\Captcha;

class AuthController
{
    protected $user;

    public function __construct(UserService $userService)
    {
        $this->user = $userService;
    }

    public function index()
    {
        return Inertia::render('Auth/Login');
    }

    public function captcha()
    {
        return response()->json(['captcha' => Captcha::src('default')]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $input = $request->username;

        /**
         * RULE 1 — Admin & Instructor → login via Laravel email ONLY
         */
        if ($this->isAdminOrInstructor($input)) {
            return $this->loginAdminInstructor($request, $input);
        }

        /**
         * RULE 2 — Participant → login via NIP ONLY (SSO first)
         */
        return $this->loginParticipant($request, $input);
    }

    private function isAdminOrInstructor($email)
    {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return false;

        $user = User::where('email', $email)->first();
        if (!$user) return false;

        return $user->hasRole('Admin') || $user->hasRole('Instructor');
    }

    /**
     * LOGIN ADMIN / INSTRUCTOR (Laravel only)
     */
    private function loginAdminInstructor(Request $request, $email)
    {
        $credentials = ['email' => $email, 'password' => $request->password];

        if (!Auth::attempt($credentials)) {
            return back()->withErrors(['email_or_username' => 'Pastikan username dan password yang digunakan benar. Silahkan ulangi beberapa saat lagi']);
        }

        $user = Auth::user();

        Log::info("LOGIN SUCCESS (Laravel): {$user->email} | role: {$user->getRoleNames()}");

        $request->session()->regenerate();
        return $this->redirectByRole($user);
    }

    /**
     * LOGIN PARTICIPANT — NIP only, SSO first, fallback Laravel
     */
    private function loginParticipant(Request $request, $nip)
    {
        // participant wajib login NIP, tidak boleh email
        if (filter_var($nip, FILTER_VALIDATE_EMAIL)) {
            return back()->withErrors([
                'email_or_username' => 'Participant harus login menggunakan NIP.'
            ]);
        }

        $userMeta = find_user_by_nip($nip);

        /**
         * CASE 1 — Sudah ada user di Laravel
         */
        if ($userMeta && $userMeta->user) {
            $user = $userMeta->user;

            if (!$user->hasRole('Participant')) {
                return back()->withErrors([
                    'email_or_username' => 'Role ini tidak dapat login menggunakan NIP.'
                ]);
            }

            /**
             * 1️⃣ Coba login Laravel DULU 
             */
            if (Auth::attempt(['email' => $user->email, 'password' => $request->password])) {
                Log::info("LOGIN PARTICIPANT: Laravel success for NIP {$nip}");
                $request->session()->regenerate();
                return $this->redirectByRole($user);
            }

            /**
             * 2️⃣ Jika Laravel gagal → fallback ke SSO
             */
            if ($this->attemptSSOLogin($request, $nip, $user)) {
                Log::info("LOGIN PARTICIPANT: Fallback SSO success for NIP {$nip}");
                return $this->redirectByRole($user);
            }

            /**
             * 3️⃣ Gagal semua
             */
            Log::warning("LOGIN PARTICIPANT FAILED: NIP {$nip}");
            return back()->withErrors(['email_or_username' => 'Pastikan username dan password yang digunakan benar. Silahkan ulangi beberapa saat lagi']);
        }

        /**
         * CASE 2 — user belum ada → first-time SSO provisioning
         */
        if ($this->handleSSOFirstLogin($request, $nip)) {
            Log::info("LOGIN PARTICIPANT: First time SSO provisioning success for NIP {$nip}");
            return to_route('home');
        }

        Log::error("LOGIN PARTICIPANT FAILED: SSO provisioning failed for NIP {$nip}");
        return back()->withErrors(['email_or_username' => 'Autentikasi gagal.']);
    }


    /**
     * Attempt login via SSO + update Laravel password
     */
    private function attemptSSOLogin(Request $request, $nip, User $user)
    {
        try {
            $response = Http::withHeaders([
                'apikey' => env('API_KEY')
            ])->post(env('SSO_BASE_URL') . '/oauth/token', [
                'username'      => $nip,
                'password'      => $request->password,
                'grant_type'    => 'password',
                'client_id'     => env('SSO_CLIENT_ID'),
                'client_secret' => env('SSO_CLIENT_SECRET'),
            ]);

            if (!$response->successful() || !isset($response['access_token'])) {
                Log::warning("SSO LOGIN FAILED for NIP {$nip}");
                return false;
            }

            Log::info("SSO LOGIN SUCCESS for NIP {$nip}");

            // sinkron password Laravel
            $user->update([
                'password' => Hash::make($request->password)
            ]);

            Auth::login($user);
            $request->session()->regenerate();

            return true;
        } catch (\Throwable $e) {
            Log::error("attemptSSOLogin ERROR for NIP {$nip}: " . $e->getMessage());
            return false;
        }
    }


    /**
     * First-time provisioning via SSO
     */
    private function handleSSOFirstLogin(Request $request, $nip)
    {
        try {
            // Step 1: test login
            $response = Http::retry(3, 100)->withHeaders([
                'apikey' => env('API_KEY')
            ])->post(env('SSO_BASE_URL') . '/oauth/token', [
                'username'      => $nip,
                'password'      => $request->password,
                'grant_type'    => 'password',
                'client_id'     => env('SSO_CLIENT_ID'),
                'client_secret' => env('SSO_CLIENT_SECRET'),
            ]);

            if (!$response->successful() || !isset($response['access_token'])) return false;

            $token = 'Bearer ' . $response['access_token'];

            // Step 2: get profile
            $dataApi = Http::retry(3, 100)->withHeaders([
                'apikey' => env('API_KEY'),
                'auth'   => $token
            ])->post(env('SSO_BASE_URL') . '/api/get_nip_data');

            if (!$dataApi->successful()) return false;

            DB::beginTransaction();

            try {
                $data = $dataApi->json();

                $user = User::create([
                    'name'     => $data['fv_nama'] ?? "User $nip",
                    'email'    => $data['fv_email'] ?? "{$nip}@example.com",
                    'password' => Hash::make($request->password),
                ]);

                $this->user->storeUserMeta($user->id, $data);
                $user->assignRole('Participant');

                Auth::login($user);
                $request->session()->regenerate();

                DB::commit();
                return true;
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('SSO provisioning failed: ' . $e->getMessage());
                return false;
            }
        } catch (\Throwable $e) {
            Log::error('handleSSOFirstLogin error: ' . $e->getMessage());
            return false;
        }
    }

    private function redirectByRole($user)
    {
        if ($user->hasRole('Admin')) {
            return Inertia::location(route('admin.dashboard'));
        }

        if ($user->hasRole('Instructor')) {
            return to_route('instructor.dashboard');
        }

        return to_route('home');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('sign-in.index');
    }
}
