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
        ], [
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
        # validate
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'captcha' => 'required|captcha'
        ],  [
            'captcha.captcha' => 'The CAPTCHA is incorrect. Please try again.'
        ]);

        # find user by username (nip)
        $userNip = UserMeta::with('user')
            ->where('key', 'nip')
            ->where('value', $request->username)
            ->first();

        # if the user is found
        if ($userNip) {
            # set credentials
            $credentials = [
                'email' => $userNip->email,
                'password' => $request->password,
            ];

            # login
            if (Auth::attempt($credentials)) {
                // regenare session
                $request->session()->regenerate();
                # return to home 
                return to_route('home');
            }
            // dd($request->all());

            # condition if password wrong or change 
            try {
                # request oauth
                $response = Http::retry(3, 100)->withHeaders([
                    'apikey' => env('API_KEY')
                ])->post(env('SSO_BASE_URL') . '/oauth/token', [
                    'username' => $request->username,
                    'password' => $request->password,
                    'grant_type'   => 'password',
                    'client_id'    => env('SSO_CLIENT_ID'),
                    'client_secret' => env('SSO_CLIENT_SECRET'),
                ]);

                # if success oauth
                if ($response->successful() && isset($response->json()['access_token'])) {
                    # set bearer token
                    $token = 'Bearer ' . $response->json()['access_token'];
                    Session::put('token', $token);

                    # get data user
                    try {
                        $participantApi = Http::retry(3, 100)->withHeaders([
                            'apikey' => env('API_KEY'),
                            'auth' => $token
                        ])->post(env('SSO_BASE_URL') . '/api/get_nip_data');

                        # if success request data user
                        if ($participantApi->successful()) {
                            try {
                                DB::beginTransaction();
                                # update password with new password 
                                $user = $userNip->user;
                                $user->password = Hash::make($request->password);
                                $user->save();

                                DB::commit();
                                // regenare session
                                $request->session()->regenerate();
                                # login
                                Auth::login($user);
                                # redirect to home
                                return to_route('home');
                            } catch (\Throwable $th) {
                                DB::rollBack();

                                return redirect()->back()->with('danger', 'Something went wrong.');
                            }
                        }
                    } catch (\Throwable $th) {
                        return redirect()->back()->with('danger', $th->getMessage());
                    }
                }
            } catch (\Throwable $th) {
                return redirect()->back()->with('danger', $th->getMessage());
            }
        }

        # if the user is not found (never login), login SSO to get data user 

        # request login oauth
        try {
            $response = Http::retry(3, 100)->withHeaders([
                'apikey' => env('API_KEY')
            ])->post(env('SSO_BASE_URL') . '/oauth/token', [
                'username' => $request->username,
                'password' => $request->password,
                'grant_type'   => 'password',
                'client_id'    => env('SSO_CLIENT_ID'),
                'client_secret' => env('SSO_CLIENT_SECRET'),
            ]);

            # if success response oauth & get access token
            if ($response->successful() && isset($response->json()['access_token'])) {
                # set bearer token
                $token = 'Bearer ' . $response->json()['access_token'];
                Session::put('token', $token);

                # request data user
                try {
                    $participantApi = Http::retry(3, 100)->withHeaders([
                        'apikey' => env('API_KEY'),
                        'auth' => $token
                    ])->post(env('SSO_BASE_URL') . '/api/get_nip_data');

                    # if success request data user
                    if ($participantApi->successful()) {
                        try {
                            DB::beginTransaction();
                            # create new user
                            $user = User::create([
                                'name' => $participantApi->json()['fv_nama'],
                                'email' => $participantApi->json()['fv_email'],
                                'password' => Hash::make($request->password)
                            ]);
                            # create new meta user
                            $this->user->storeUserMeta($user->id, $participantApi->json());
                            # create role user
                            $user->assignRole('Participant');
                            # login with user 
                            Auth::login($user);

                            DB::commit();

                            return to_route('home');
                        } catch (\Throwable $th) {
                            DB::rollBack();

                            return redirect()->back()->with('danger', $th->getMessage());
                        }
                    }
                } catch (\Throwable $th) {
                    # if failed request data user
                    return redirect()->back()->with('danger', $th->getMessage());
                }
            }
        } catch (\Throwable $th) {
            # if failed request sso
            return redirect()->back()->with('danger', $th->getMessage());
        }
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
