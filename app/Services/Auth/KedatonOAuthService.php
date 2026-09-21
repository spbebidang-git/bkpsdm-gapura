<?php

namespace App\Services\Auth;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class KedatonOAuthService
{
    public function isConfigured(): bool
    {
        return filled(config('services.kedaton.base_url'))
            && filled(config('services.kedaton.client_id'))
            && filled(config('services.kedaton.client_secret'))
            && filled(config('services.kedaton.redirect_uri'))
            && filled(config('services.kedaton.data_key'));
    }

    public function authorizationUrl(string $state): string
    {
        $query = array_filter([
            'client_id' => config('services.kedaton.client_id'),
            'redirect_uri' => config('services.kedaton.redirect_uri'),
            'response_type' => 'code',
            'scope' => config('services.kedaton.scope'),
            'state' => $state,
        ], static fn ($value) => $value !== null && $value !== '');

        return rtrim(config('services.kedaton.base_url'), '/').'/oauth/authorize?'.http_build_query($query);
    }

    public function newState(): string
    {
        return Str::random(64);
    }

    public function exchangeCode(string $code): Response
    {
        return Http::timeout(10)
            ->acceptJson()
            ->asForm()
            ->post(rtrim(config('services.kedaton.base_url'), '/').'/oauth/token', [
                'grant_type' => 'authorization_code',
                'client_id' => config('services.kedaton.client_id'),
                'client_secret' => config('services.kedaton.client_secret'),
                'code' => $code,
                'redirect_uri' => config('services.kedaton.redirect_uri'),
            ]);
    }

    /**
     * The collection defines this endpoint as the authoritative validation
     * point. Its response must contain a verified NIP before Gapura creates a
     * local participant session.
     */
    public function checkToken(string $accessToken): Response
    {
        return Http::timeout(10)
            ->acceptJson()
            ->asForm()
            ->withToken($accessToken)
            ->post(rtrim(config('services.kedaton.base_url'), '/').'/api/token/check', [
                'client_id' => config('services.kedaton.client_id'),
                'client_secret' => config('services.kedaton.client_secret'),
            ]);
    }

    public function verifiedNip(array $payload): ?string
    {
        foreach (['nip', 'fc_nipbr', 'fv_nipbr'] as $key) {
            $value = $payload[$key] ?? null;
            if (is_scalar($value) && preg_match('/^\d{3,32}$/', trim((string) $value))) {
                return trim((string) $value);
            }
        }

        // Kedaton deployments may wrap the token payload in `data` or `user`.
        // Keep the accepted keys explicit so an unrelated numeric ID can never
        // be used as the application's SSO identity.
        foreach (['data', 'user'] as $key) {
            $value = $payload[$key] ?? null;
            if (is_array($value) && ($nip = $this->verifiedNip($value)) !== null) {
                return $nip;
            }
        }

        return null;
    }

    public function getEmployee(string $nip): Response
    {
        return Http::timeout(10)
            ->acceptJson()
            ->asForm()
            ->post(rtrim(config('services.kedaton.base_url'), '/').'/api/get_data_api', [
                'key' => config('services.kedaton.data_key'),
                'nip' => $nip,
                'nik' => '',
                'uker_kode' => '',
                'tipe_data' => config('services.kedaton.data_type', 'pns'),
            ]);
    }
}
