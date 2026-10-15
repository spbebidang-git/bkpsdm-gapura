<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Auth\SsoAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class AuthController
{
    public function __construct(protected SsoAuthService $sso) {}

    /**
     * SSO login → Admin only → Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:255',
        ]);

        try {
            $username = trim($request->username);
            $user = hash_equals((string) config('services.sso.admin_username', 'elearning'), $username)
                ? $this->sso->loginPrestigeSuperAdmin($username, $request->password)
                : $this->sso->loginOrProvision($username, $request->password);
        } catch (RuntimeException) {
            throw ValidationException::withMessages([
                'username' => ['Kredensial SSO tidak valid.'],
            ]);
        }

        if (! $user->hasAnyRole(['super_admin', 'administrator'])) {
            throw ValidationException::withMessages([
                'username' => ['Hanya akun Admin yang dapat mengakses API.'],
            ]);
        }

        $deviceName = $request->device_name ?? 'iop-client';
        $token = $user->createToken($deviceName);

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token->plainTextToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'nip' => $user->nip,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Token berhasil di-revoke.',
        ]);
    }
}
