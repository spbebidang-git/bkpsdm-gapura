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
use Inertia\Inertia;
use Mews\Captcha\Facades\Captcha;

class AuthController
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        return Inertia::render('Auth/Login');
    }

    public function captcha()
    {
        return response()->json(Captcha::create('default', true));
    }

    public function store(Request $request)
    {
        $rules = [
            'username' => 'required|string',
            'password' => 'required|string',
            'captcha' => config('captcha.disable')
                ? 'required|string'
                : 'required|string|captcha_api:' . $request->input('captcha_key') . ',default',
        ];

        if (!config('captcha.disable')) {
            $rules['captcha_key'] = 'required|string';
        }

        $request->validate($rules, [
            'captcha.captcha' => 'The CAPTCHA is incorrect. Please try again.',
            'captcha.captcha_api' => 'The CAPTCHA is incorrect. Please try again.',
        ]);

        $input = $request->username;

        // RULE 1 — Admin & Instructor (Login via Email)
        if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
            return $this->loginAdminInstructor($request, $input);
        }

        // RULE 2 — Participant (Login via NIP)
        return $this->loginParticipant($request, $input);
    }

    private function loginAdminInstructor(Request $request, $email)
    {
        $user = User::where('email', $email)->first();

        if (!$user || $user->hasRole('Participant')) {
            return back()->withErrors(['email_or_username' => 'Email atau password salah.']);
        }

        if (!Auth::attempt(['email' => $email, 'password' => $request->password])) {
            return back()->withErrors(['email_or_username' => 'Email atau password salah.']);
        }

        $request->session()->regenerate();
        return $this->redirectByRole($user);
    }

    private function loginParticipant(Request $request, $nip)
    {
        $userMeta = find_user_by_nip($nip);

        if ($userMeta && $userMeta->user) {
            $user = $userMeta->user;

            if (!$user->hasRole('Participant')) {
                return back()->withErrors(['email_or_username' => 'NIP terdaftar dengan hak akses berbeda.']);
            }

            // 1. Coba login lokal Laravel
            if (Auth::attempt(['email' => $user->email, 'password' => $request->password])) {

                // CEK DATA KRUSIAL: Jika status_pegawai kosong, paksa update dari SSO
                $metaStatus = UserMeta::where('user_id', $user->id)
                    ->where('key', 'status_pegawai')
                    ->first();

                if (!$metaStatus || empty($metaStatus->value)) {
                    Log::info("Data meta kosong untuk NIP {$nip}, sinkronisasi ulang ke SSO.");
                    $this->syncUserMetaFromSSO($nip, $request->password, $user);
                }

                $request->session()->regenerate();
                return $this->redirectByRole($user);
            }

            // 2. Fallback SSO (Jika password lokal belum sinkron)
            if ($this->attemptSSOAndSync($request, $nip, $user)) {
                return $this->redirectByRole($user);
            }

            return back()->withErrors(['email_or_username' => 'Kredensial salah atau SSO tidak merespon.']);
        }
        // CASE 2 — User Baru (First-time provisioning)
        if ($this->handleSSOFirstLogin($request, $nip)) {
            return to_route('home');
        }

        return back()->withErrors(['email_or_username' => 'Gagal autentikasi SSO atau data NIP tidak ditemukan.']);
    }

    /**
     * Memperbarui data Meta tanpa mengganggu alur login utama
     */
    private function syncUserMetaFromSSO($nip, $password, User $user)
    {
        try {
            $response = $this->callSSOApi($nip, $password);
            if (!$response->successful()) return;

            $dataApi = Http::withHeaders([
                'apikey' => config('services.sso.api_key'),
                'Authorization' => 'Bearer ' . $response['access_token'],
                'Accept' => 'application/json'
            ])->post(config('services.sso.base_url') . '/api/get_nip_data');

            if ($dataApi->successful()) {
                $data = $dataApi->json();

                if (is_array($data)) {
                    $this->userService->storeUserMeta($user->id, $data, $nip);

                    // Update nama jika ada perubahan di data pusat
                    $user->update(['name' => $data['fv_nama'] ?? $user->name]);
                } else {
                    Log::error("Sync Meta Error NIP {$nip}: Response JSON is null or not an array.");
                }
            }
        } catch (\Exception $e) {
            Log::error("Sync Meta Error NIP {$nip}: " . $e->getMessage());
        }
    }

    private function attemptSSOAndSync(Request $request, $nip, User $user)
    {
        try {
            $response = $this->callSSOApi($nip, $request->password);
            if ($response->successful() && isset($response['access_token'])) {
                // Sinkron password lokal
                $user->update(['password' => Hash::make($request->password)]);

                // Ambil & Update meta data terbaru
                $this->syncUserMetaFromSSO($nip, $request->password, $user);

                Auth::login($user);
                $request->session()->regenerate();
                return true;
            }
        } catch (\Exception $e) {
            Log::error("SSO Login Error NIP {$nip}: " . $e->getMessage());
        }
        return false;
    }

    private function handleSSOFirstLogin(Request $request, $nip)
    {
        try {
            $response = $this->callSSOApi($nip, $request->password);
            if (!$response->successful()) return false;

            $dataApi = Http::withHeaders([
                'apikey' => config('services.sso.api_key'),
                'Authorization' => 'Bearer ' . $response['access_token'],
                'Accept' => 'application/json'
            ])->post(config('services.sso.base_url') . '/api/get_nip_data');

            if (!$dataApi->successful()) return false;

            $data = $dataApi->json();

            if (!is_array($data)) {
                Log::error("Provisioning gagal NIP {$nip}: Data dari SSO kosong atau bukan array.");
                return false;
            }

            // Jika SSO tidak mengembalikan email, buat email dari NIP
            $email = empty($data['fv_email']) ? "{$nip}@gresikkab.go.id" : $data['fv_email'];

            // Cek jika email ternyata sudah ada di database
            if (User::where('email', $email)->exists()) {
                Log::error("Provisioning gagal: Email {$email} sudah digunakan.");
                return false;
            }

            return DB::transaction(function () use ($data, $request, $email, $nip) {
                $user = User::create([
                    'name'     => $data['fv_nama'] ?? "User $email",
                    'email'    => $email,
                    'password' => Hash::make($request->password),
                ]);

                $this->userService->storeUserMeta($user->id, $data, $nip);
                $user->assignRole('Participant');

                Auth::login($user);
                $request->session()->regenerate();
                return true;
            });
        } catch (\Exception $e) {
            Log::error("Provisioning Error NIP {$nip}: " . $e->getMessage());
            return false;
        }
    }

    private function callSSOApi($nip, $password)
    {
        return Http::retry(2, 100)->withHeaders([
            'apikey' => config('services.sso.api_key')
        ])->post(config('services.sso.base_url') . '/oauth/token', [
            'username'      => $nip,
            'password'      => $password,
            'grant_type'    => 'password',
            'client_id'     => config('services.sso.client_id'),
            'client_secret' => config('services.sso.client_secret'),
        ]);
    }

    private function redirectByRole($user)
    {
        if ($user->hasRole('Admin')) {
            // Hard reload untuk Admin (Blade)
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
