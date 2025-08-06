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
        return response()->json([
            'captcha' => Captcha::src('default')
        ]);
    }

    public function createAdmin()
    {
        return view('admin.login');
    }

    public function storeAdmin(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
            'captcha' => 'required|captcha'
        ], [
            'captcha.captcha' => 'The CAPTCHA is incorrect. Please try again.'
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return to_route('admin.dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function createInstructor()
    {
        return Inertia::render('Auth/LoginInstructor');
    }

    public function storeInstructor(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
            'captcha' => 'required|captcha'
        ], [
            'captcha.captcha' => 'The CAPTCHA is incorrect. Please try again.'
        ]);

        if (Auth::attempt($request->only('email', 'password'))) {
            $request->session()->regenerate();
            return to_route('instructor.dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function createParticipant()
    {
        return Inertia::render('Auth/LoginParticipant');
    }

    public function storeParticipant(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'captcha' => 'required|captcha'
        ], [
            'captcha.captcha' => 'The CAPTCHA is incorrect. Please try again.'
        ]);

        $userMeta = UserMeta::with('user')
            ->where('key', 'nip')
            ->where('value', $request->username)
            ->first();

        if ($userMeta) {
            $credentials = [
                'email' => $userMeta->user->email,
                'password' => $request->password,
            ];

            if ($this->SyncUserMeta($userMeta->user->id, $request->username, $request->password)) {
                if (Auth::attempt($credentials)) {
                    $request->session()->regenerate();
                    return to_route('home');
                }
            }

            // Try to refresh password via SSO if failed
            if ($this->handleSSOPasswordRefresh($request, $userMeta->user)) {
                return to_route('home');
            }

            return back()->withErrors(['danger' => 'Something went wrong.']);
        }

        // First-time SSO login
        if ($this->handleSSOFirstLogin($request)) {
            return to_route('home');
        }

        return back()->withErrors(['danger' => 'Something went wrong.']);
    }

    public function logout(Request $request)
    {
        $role = $request->user()->roles->first()->name;

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return match ($role) {
            'Admin' => to_route('sign-in.admin.create'),
            'Instructor' => to_route('sign-in.instructor.create'),
            default => to_route('sign-in.participant.create'),
        };
    }

    private function SyncUserMeta($userId, $username, $password)
    {
        if (get_user_meta($userId, 'status_pegawai')) {
            return true;
        }

        try {
            $response = Http::retry(3, 100)->withHeaders([
                'apikey' => env('API_KEY')
            ])->post(env('SSO_BASE_URL') . '/oauth/token', [
                'username'      => $username,
                'password'      => $password,
                'grant_type'    => 'password',
                'client_id'     => env('SSO_CLIENT_ID'),
                'client_secret' => env('SSO_CLIENT_SECRET'),
            ]);

            if ($response->successful() && isset($response->json()['access_token'])) {
                $token = 'Bearer ' . $response->json()['access_token'];
                Session::put('token', $token);

                $participantApi = Http::retry(3, 100)->withHeaders([
                    'apikey' => env('API_KEY'),
                    'auth'   => $token
                ])->post(env('SSO_BASE_URL') . '/api/get_nip_data');

                if ($participantApi->successful()) {
                    $this->user->storeUserMeta($userId, $participantApi->json());
                    Log::info('SyncUserMeta success', ['user_id' => $userId]);
                    return true;
                }

                Log::warning('SyncUserMeta: Failed to fetch user data.', ['response' => $participantApi->body()]);
            } else {
                Log::warning('SyncUserMeta: OAuth failed.', ['response' => $response->body()]);
            }
        } catch (\Throwable $e) {
            Log::error('SyncUserMeta: Exception', ['message' => $e->getMessage()]);
        }

        return false;
    }

    private function handleSSOPasswordRefresh(Request $request, $user)
    {
        try {
            $response = Http::retry(3, 100)->withHeaders([
                'apikey' => env('API_KEY')
            ])->post(env('SSO_BASE_URL') . '/oauth/token', [
                'username'      => $request->username,
                'password'      => $request->password,
                'grant_type'    => 'password',
                'client_id'     => env('SSO_CLIENT_ID'),
                'client_secret' => env('SSO_CLIENT_SECRET'),
            ]);

            if ($response->successful() && isset($response->json()['access_token'])) {
                $token = 'Bearer ' . $response->json()['access_token'];
                Session::put('token', $token);

                $participantApi = Http::retry(3, 100)->withHeaders([
                    'apikey' => env('API_KEY'),
                    'auth'   => $token
                ])->post(env('SSO_BASE_URL') . '/api/get_nip_data');

                if ($participantApi->successful()) {
                    DB::beginTransaction();
                    $user->update(['password' => Hash::make($request->password)]);
                    DB::commit();

                    Auth::login($user);
                    $request->session()->regenerate();
                    return true;
                }
            }
        } catch (\Throwable $th) {
            // logging opsional
        }

        return false;
    }

    private function handleSSOFirstLogin(Request $request)
    {
        try {
            $response = Http::retry(3, 100)->withHeaders([
                'apikey' => env('API_KEY')
            ])->post(env('SSO_BASE_URL') . '/oauth/token', [
                'username'      => $request->username,
                'password'      => $request->password,
                'grant_type'    => 'password',
                'client_id'     => env('SSO_CLIENT_ID'),
                'client_secret' => env('SSO_CLIENT_SECRET'),
            ]);

            if ($response->successful() && isset($response->json()['access_token'])) {
                $token = 'Bearer ' . $response->json()['access_token'];
                Session::put('token', $token);

                $participantApi = Http::retry(3, 100)->withHeaders([
                    'apikey' => env('API_KEY'),
                    'auth'   => $token
                ])->post(env('SSO_BASE_URL') . '/api/get_nip_data');

                if ($participantApi->successful()) {
                    DB::beginTransaction();

                    $user = User::create([
                        'name'     => $participantApi->json()['fv_nama'],
                        'email'    => $participantApi->json()['fv_email'],
                        'password' => Hash::make($request->password),
                    ]);

                    $this->user->storeUserMeta($user->id, $participantApi->json());
                    $user->assignRole('Participant');

                    Auth::login($user);
                    DB::commit();

                    return true;
                }
            }
        } catch (\Throwable $th) {
            // logging opsional
        }

        return false;
    }
}
