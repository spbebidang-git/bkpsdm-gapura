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

    public function captcha()
    {
        return response()->json(['captcha' => Captcha::src('default')]);
    }

    public function index()
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request)
    {
        $request->validate(
            [
                'username' => 'required|string',
                'password' => 'required|string',
                'captcha' => 'required|captcha',
            ],
            [
                'captcha.captcha' => 'CAPTCHA salah, silakan coba lagi.'
            ]
        );

        $input = $request->username;

        // Jika input adalah email -> gunakan login Laravel biasa
        if (filter_var($input, FILTER_VALIDATE_EMAIL)) {
            return $this->handleLaravelLogin($request, $input);
        }

        // Kalau bukan email -> asumsikan NIP -> SSO
        return $this->handleSSOLogin($request, $input);
    }

    private function handleLaravelLogin(Request $request, $email)
    {
        $credentials = ['email' => $email, 'password' => $request->password];

        // 1. Coba login Laravel biasa
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return $this->redirectByRole(Auth::user());
        }

        // 2. Jika gagal → cek apakah user ini punya NIP (SSO-linked)
        $user = User::where('email', $email)->first();

        if ($user) {
            $nip = get_user_meta($user->id, 'nip');

            if ($nip) {
                // Coba login SSO
                if ($this->attemptSSOLogin($request, $nip, $user)) {
                    return $this->redirectByRole($user);
                }
            }
        }

        // 3. Jika semuanya gagal
        return back()->withErrors(['email_or_username' => 'Kredensial tidak valid.']);
    }

    /**
     * Handle SSO login by NIP.
     * - If an existing user (user_meta.nip) is found: login and redirect based on role.
     * - If not found: attempt first-time SSO provisioning (handleSSOFirstLogin).
     */
    private function handleSSOLogin(Request $request, $nip)
    {
        $userMeta = UserMeta::with('user')
            ->where('key', 'nip')
            ->where('value', $nip)
            ->first();

        // Jika sudah punya akun Laravel
        if ($userMeta && $userMeta->user) {
            $user = $userMeta->user;

            if ($this->attemptSSOLogin($request, $nip, $user)) {
                return $this->redirectByRole($user);
            }

            return back()->withErrors(['email_or_username' => 'Password salah.']);
        }

        // First-time SSO login
        if ($this->handleSSOFirstLogin($request, $nip)) {
            return to_route('home');
        }

        return back()->withErrors(['email_or_username' => 'Autentikasi SSO gagal.']);
    }

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

            if (!$response->successful() || !isset($response->json()['access_token'])) {
                return false; // password salah
            }

            // Password valid → sinkron password Laravel
            $user->update([
                'password' => Hash::make($request->password)
            ]);

            // Login Laravel
            Auth::login($user);
            $request->session()->regenerate();

            return true;
        } catch (\Throwable $e) {
            Log::error('attemptSSOLogin error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * First-time SSO provisioning — ambil data dari SSO, buat user & user_meta, lalu login.
     * Mengembalikan true jika berhasil.
     */
    private function handleSSOFirstLogin(Request $request, $nip)
    {
        try {
            $response = Http::retry(3, 100)->withHeaders([
                'apikey' => env('API_KEY')
            ])->post(env('SSO_BASE_URL') . '/oauth/token', [
                'username'      => $nip,
                'password'      => $request->password,
                'grant_type'    => 'password',
                'client_id'     => env('SSO_CLIENT_ID'),
                'client_secret' => env('SSO_CLIENT_SECRET'),
            ]);

            if ($response->successful() && isset($response->json()['access_token'])) {
                $token = 'Bearer ' . $response->json()['access_token'];
                // simpan token jika perlu
                Session::put('token', $token);

                $dataApi = Http::retry(3, 100)->withHeaders([
                    'apikey' => env('API_KEY'),
                    'auth'   => $token
                ])->post(env('SSO_BASE_URL') . '/api/get_nip_data');

                if ($dataApi->successful()) {
                    DB::beginTransaction();

                    try {
                        $data = $dataApi->json();

                        // buat user baru (pastikan unique email jika mungkin)
                        $user = User::create([
                            'name'     => $data['fv_nama'] ?? 'User ' . $nip,
                            'email'    => $data['fv_email'] ?? "{$nip}@example.com",
                            'password' => Hash::make($request->password),
                        ]);

                        // simpan meta via service Anda (storeUserMeta harus menangani array $data dengan key 'nip' dsb.)
                        $this->user->storeUserMeta($user->id, $data);

                        // beri peran default
                        $user->assignRole('Participant');

                        // login user yang baru dibuat
                        Auth::login($user);
                        $request->session()->regenerate();

                        DB::commit();

                        return true;
                    } catch (\Throwable $e) {
                        DB::rollBack();
                        Log::error('SSO provisioning failed (DB): ' . $e->getMessage(), [
                            'nip' => $nip,
                            'trace' => $e->getTraceAsString()
                        ]);
                        return false;
                    }
                } else {
                    Log::warning('SSO get_nip_data failed', [
                        'nip' => $nip,
                        'status' => $dataApi->status(),
                        'body' => $dataApi->body()
                    ]);
                }
            } else {
                Log::warning('SSO token request failed', [
                    'nip' => $nip,
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('handleSSOFirstLogin error', ['message' => $e->getMessage()]);
        }

        return false;
    }

    private function redirectByRole($user)
    {
        if ($user->hasRole('Admin')) {
            // Jangan lewat inertia, redirect langsung ke Blade
            // return redirect()->to(route('admin.dashboard'));
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
