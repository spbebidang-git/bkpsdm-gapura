<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController
{
    /**
     * Login via API dan dapatkan bearer token.
     * Hanya akun dengan role Admin yang diizinkan.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string|max:4096',
            'device_name' => 'nullable|string|max:100',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user || !$user->hasRole('Admin') || $user->hasRole('Participant') || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial yang diberikan tidak valid.'],
            ]);
        }

        $deviceName = $request->device_name ?? 'iop-client';
        $expirationMinutes = max(1, (int) config('sanctum.expiration'));
        $user->tokens()->where('name', $deviceName)->delete();
        $expiresAt = now()->addMinutes($expirationMinutes);
        $token = $user->createToken($deviceName, ['api:read'], $expiresAt);

        return response()->json([
            'message' => 'Login berhasil.',
            'token' => $token->plainTextToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    /**
     * Logout — revoke token yang sedang digunakan.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Token berhasil di-revoke.',
        ]);
    }
}
