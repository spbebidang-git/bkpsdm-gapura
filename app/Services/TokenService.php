<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TokenService
{
    private $fileName = 'token.txt';

    /**
     * Ambil token yang valid. Jika expired secara lokal, refresh otomatis.
     */
    public function getToken(): string
    {
        if ($this->isExpired()) {
            return $this->refreshToken();
        }

        $encrypted = Storage::get($this->fileName);
        $data = json_decode(Crypt::decryptString($encrypted), true);

        return 'Bearer ' . $data['access_token'];
    }

    /**
     * Paksa refresh token dari SSO tanpa cek cache.
     * Dipanggil saat API mengembalikan 401 (token stale di server).
     */
    public function forceRefresh(): string
    {
        if (app()->isLocal()) {
            Log::debug('[TokenService] Force refresh token dipanggil.');
        }

        // Hapus cache token agar refreshToken menyimpan ulang
        if (Storage::exists($this->fileName)) {
            Storage::delete($this->fileName);
        }

        return $this->refreshToken();
    }

    private function setToken(string $token, int $expiresIn): void
    {
        $data = [
            'access_token' => $token,
            'expires_at' => Carbon::now()->addSeconds($expiresIn)->timestamp
        ];

        $encrypted = Crypt::encryptString(json_encode($data));
        Storage::put($this->fileName, $encrypted);
    }

    private function isExpired(): bool
    {
        if (!Storage::exists($this->fileName)) {
            return true;
        }

        $encrypted = Storage::get($this->fileName);
        $data = json_decode(Crypt::decryptString($encrypted), true);

        return Carbon::now()->timestamp >= $data['expires_at'];
    }

    private function refreshToken(): string
    {
        if (app()->isLocal()) {
            Log::debug('[TokenService] Melakukan refresh token dari SSO.', [
                'username' => env('SSO_ADMIN_USERNAME'),
                'url' => env('SSO_BASE_URL') . '/oauth/token',
            ]);
        }

        $response = Http::retry(3, 5000, function ($exception) {
            return $exception instanceof \Illuminate\Http\Client\ConnectionException
                || ($exception instanceof \Illuminate\Http\Client\RequestException && $exception->response->serverError());
        }, throw: false)->withHeaders([
                    'apikey' => env('API_KEY')
                ])->post(env('SSO_BASE_URL') . '/oauth/token', [
                    'username' => env('SSO_ADMIN_USERNAME'),
                    'password' => env('SSO_ADMIN_PASS'),
                    'grant_type' => 'password',
                    'client_id' => env('SSO_CLIENT_ID'),
                    'client_secret' => env('SSO_CLIENT_SECRET'),
                ]);

        if (app()->isLocal()) {
            Log::debug('[TokenService] SSO OAuth response.', [
                'http_status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        if ($response->failed()) {
            throw new \Exception(
                'Gagal mengambil token dari SSO (HTTP ' . $response->status() . '): ' . $response->body()
            );
        }

        $data = $response->json();

        $this->setToken($data['access_token'], $data['expires_in']);

        return 'Bearer ' . $data['access_token'];
    }
}
