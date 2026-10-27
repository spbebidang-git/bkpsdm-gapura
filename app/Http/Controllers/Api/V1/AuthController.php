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
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:255',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Kredensial yang diberikan tidak valid.'],
            ]);
        }

        if (!$user->hasRole('Admin')) {
            throw ValidationException::withMessages([
                'email' => ['Hanya akun dengan role Admin yang dapat mengakses API.'],
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
            ],
        ]);
    }

    /**
     * Logout - revoke token yang sedang digunakan.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Token berhasil di-revoke.',
        ]);
    }
}
