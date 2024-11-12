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
            'username' => 'required|string',
            'password' => 'required|string',
            'captcha' => 'required|captcha'
        ],  [
            'captcha.captcha' => 'The CAPTCHA is incorrect. Please try again.'  // Pesan error khusus
        ]);

        $response = Http::withHeaders([
            'apikey' => env('API_KEY')
        ])->post(env('SSO_BASE_URL') . '/oauth/token', [
            'username' => $request->username,
            'password' => $request->password,
            'grant_type'   => 'password',
            'client_id'    => env('SSO_CLIENT_ID'),
            'client_secret' => env('SSO_CLIENT_SECRET'),
        ]);

        if ($response->successful() && isset($response->json()['access_token'])) {
            $token = 'Bearer ' . $response->json()['access_token'];

            $user = User::where('email', $request->username . '@gapura.gresikkab.go.id')->first();

            if (!$user) {
                try {
                    DB::beginTransaction();

                    $user = User::create([
                        'name' => $request->username,
                        'email' => $request->username . '@gapura.gresikkab.go.id',
                        'password' => Hash::make(str()->random(12))
                    ]);

                    $user->assignRole('Admin');

                    Auth::login($user);

                    DB::commit();

                    return to_route('admin.dashboard');
                } catch (\Throwable $th) {
                    DB::rollBack();

                    return back()->withErrors([
                        'username' => 'Something went wrong.',
                    ])->onlyInput('username');
                }
            }

            Auth::login($user);

            Session::put('token', $token);

            return to_route('admin.dashboard');
        }

        return to_route('sign-in.admin.create')->with('danger', 'Something went wrong. Please try again.');
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
        ],  [
            'captcha.captcha' => 'The CAPTCHA is incorrect. Please try again.'  // Pesan error khusus
        ]);

        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
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
        ],  [
            'captcha.captcha' => 'The CAPTCHA is incorrect. Please try again.'  // Pesan error khusus
        ]);

        $response = Http::withHeaders([
            'apikey' => env('API_KEY')
        ])->post(env('SSO_BASE_URL') . '/oauth/token', [
            'username' => $request->username,
            'password' => $request->password,
            'grant_type'   => 'password',
            'client_id'    => env('SSO_CLIENT_ID'),
            'client_secret' => env('SSO_CLIENT_SECRET'),
        ]);

        sleep(1);

        if ($response->successful() && isset($response->json()['access_token'])) {
            $token = 'Bearer ' . $response->json()['access_token'];

            $userNip = UserMeta::with('user')
                ->where('key', 'nip')
                ->where('value', $request->username)
                ->first();

            $participantApi = Http::withHeaders([
                'apikey' => env('API_KEY'),
                'auth' => $token
            ])->post(env('SSO_BASE_URL') . '/api/get_nip_data');

            if (!$userNip) {
                try {
                    DB::beginTransaction();

                    $user = User::create([
                        'name' => $participantApi->json()['fv_nama'],
                        'email' => $participantApi->json()['fv_email'],
                        'password' => Hash::make(str()->random(12))
                    ]);

                    // $this->user->storeUserMeta($user->id, $participantApi->json());

                    $user->assignRole('Participant');

                    Auth::login($user);

                    DB::commit();

                    return to_route('home');
                } catch (\Throwable $th) {
                    DB::rollBack();
                    dd($th);
                    return back()->withErrors([
                        'username' => 'Something went wrong.',
                    ])->onlyInput('username');
                }
            }

            Auth::login($userNip->user);

            Session::put('token', $token);

            // $this->user->storeUserMeta($userNip->user->id, $participantApi->json());
        }


        return to_route('sign-in.participant.create')->with('danger', 'Something went wrong. Please try again.');
    }

    public function logout(Request $request)
    {
        $role = $request->user()->roles->first()->name;

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        if ($role == 'Admin') {
            return to_route('sign-in.admin.create');
        }

        if ($role == 'Instructor') {
            return to_route('sign-in.instructor.create');
        }

        if ($role == 'Admin') {
            return to_route('sign-in.participant.create');
        }
    }
}
