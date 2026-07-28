<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Services\User\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class AuthService
{
    protected $user;

    public function __construct(UserService $userService)
    {
        $this->user = $userService;
    }

    public function handleAdminLogin(Request $request)
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
            return to_route('admin.dashboard');
        }

        return back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    }

    public function handleInstructorLogin(Request $request)
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

    public function handleParticipantLogin(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'captcha' => 'required|captcha'
        ], [
            'captcha.captcha' => 'The CAPTCHA is incorrect. Please try again.'
        ]);

        $userNip = find_user_by_nip($request->username);

        if ($userNip) {
            $credentials = [
                'email' => $userNip->user->email,
                'password' => $request->password,
            ];

            if ($this->syncUserMeta($userNip->user->id, $request->username, $request->password)) {
                if (Auth::attempt($credentials)) {
                    $request->session()->regenerate();
                    return to_route('home');
                }
            }

            return $this->attemptSSOParticipantLogin($request, $userNip->user);
        }

        return $this->attemptSSOParticipantLogin($request);
    }

    protected function attemptSSOParticipantLogin(Request $request, User $existingUser = null)
    {
        try {
            $response = $this->requestSSOToken($request->username, $request->password);

            if ($response->successful() && isset($response->json()['access_token'])) {
                $token = 'Bearer ' . $response->json()['access_token'];
                Session::put('token', $token);

                $participantData = $this->fetchSSOUserData($token);
                if (!$participantData) return back()->withErrors(['danger' => 'Something went wrong.']);

                try {
                    DB::beginTransaction();

                    if ($existingUser) {
                        $existingUser->update(['password' => Hash::make($request->password)]);
                        $this->user->storeUserMeta($existingUser->id, $participantData, $request->username);
                        Auth::login($existingUser);
                    } else {
                        $user = User::create([
                            'name' => $participantData['fv_nama'],
                            'email' => $participantData['fv_email'],
                            'password' => Hash::make($request->password)
                        ]);

                        $this->user->storeUserMeta($user->id, $participantData, $request->username);
                        $user->assignRole('Participant');
                        Auth::login($user);
                    }

                    DB::commit();
                    $request->session()->regenerate();
                    return to_route('home');
                } catch (\Throwable $th) {
                    DB::rollBack();
                    return back()->with('danger', 'Something went wrong.');
                }
            }
        } catch (\Throwable $th) {
            return back()->with('danger', 'Something went wrong.');
        }

        return back()->with('danger', 'Something went wrong.');
    }

    protected function syncUserMeta($userId, $username, $password): bool
    {
        // Skip SSO re-fetch only when both metas already present
        if (get_user_meta($userId, 'status_pegawai') && get_user_meta($userId, 'nama_jabatan')) {
            return true;
        }

        try {
            $response = $this->requestSSOToken($username, $password);

            if ($response->successful() && isset($response->json()['access_token'])) {
                $token = 'Bearer ' . $response->json()['access_token'];
                Session::put('token', $token);

                $participantData = $this->fetchSSOUserData($token);
                if (!$participantData) return false;

                $this->user->storeUserMeta($userId, $participantData, $username);
                return true;
            } else {
                Log::warning('SyncUserMeta: OAuth token request failed.', [
                    'username' => $username,
                    'response' => $response->body()
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('SyncUserMeta: Exception occurred', [
                'username' => $username,
                'message' => $e->getMessage(),
            ]);
        }

        return false;
    }

    protected function requestSSOToken($username, $password)
    {
        return Http::retry(3, 100)->withHeaders([
            'apikey' => env('API_KEY')
        ])->post(env('SSO_BASE_URL') . '/oauth/token', [
            'username' => $username,
            'password' => $password,
            'grant_type' => 'password',
            'client_id' => env('SSO_CLIENT_ID'),
            'client_secret' => env('SSO_CLIENT_SECRET'),
        ]);
    }

    protected function fetchSSOUserData($token)
    {
        try {
            $response = Http::retry(3, 100)->withHeaders([
                'apikey' => env('API_KEY'),
                'auth' => $token
            ])->post(env('SSO_BASE_URL') . '/api/get_nip_data');

            return $response->successful() ? $response->json() : null;
        } catch (\Throwable $e) {
            Log::error('fetchSSOUserData failed', ['message' => $e->getMessage()]);
            return null;
        }
    }
}
