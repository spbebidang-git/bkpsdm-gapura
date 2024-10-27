<?php

namespace App\Http\Controllers\Web;

use App\Models\Management\UserMeta;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class AuthController
{
    public function createAdmin()
    {
        return view('admin.login');
    }

    public function storeAdmin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

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
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

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

        if ($response->successful()) {
            $token = 'Bearer ' . $response->json()['access_token'];

            $email = $request->username . '@elearning.gresikkab.go.id';

            $user = User::where('email', $email)->first();

            if (!$user) {
                try {
                    DB::beginTransaction();

                    $participantApi = Http::withHeaders([
                        'apikey' => env('API_KEY'),
                        'auth' => $token
                    ])->post(env('SSO_BASE_URL') . '/api/get_nip_data');

                    $user = User::create([
                        'name' => $participantApi->json()['nama'],
                        'email' => $email,
                        'password' => Hash::make(str()->random(12))
                    ]);

                    $user->assignRole('Participant');

                    Auth::login($user);

                    DB::commit();

                    return to_route('home');
                } catch (\Throwable $th) {
                    DB::rollBack();

                    return back()->withErrors([
                        'username' => 'Something went wrong.',
                    ])->onlyInput('username');
                }
            }

            Auth::login($user);

            Session::put('token', $token);
        }

        return to_route('sign-in.participant.create')->with('danger', 'Something went wrong.');
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
