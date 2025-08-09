<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class TokenService
{
    private $fileName = 'token.txt';

    public function getToken(): string
    {
        if ($this->isExpired()) {
            return $this->refreshToken();
        }

        $encrypted = Storage::get($this->fileName);
        $data = json_decode(Crypt::decryptString($encrypted), true);

        return 'Bearer ' . $data['access_token'];
    }

    private function setToken(string $token, int $expiresIn): void
    {
        $data = [
            'access_token' => $token,
            'expires_at'   => Carbon::now()->addSeconds($expiresIn)->timestamp
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
        // Request token baru ke API OAuth
        $response = Http::retry(3, 100)->withHeaders([
            'apikey' => env('API_KEY')
        ])->post(env('SSO_BASE_URL') . '/oauth/token', [
            'username'      => env('SSO_ADMIN_USERNAME'),
            'password'      => env('SSO_ADMIN_PASS'),
            'grant_type'    => 'password',
            'client_id'     => env('SSO_CLIENT_ID'),
            'client_secret' => env('SSO_CLIENT_SECRET'),
        ]);

        if ($response->failed()) {
            throw new \Exception('Gagal mengambil token dari API');
        }

        $data = $response->json();

        $this->setToken($data['access_token'], $data['expires_in']);

        return $data['access_token'];
    }
}
