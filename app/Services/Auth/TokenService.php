<?php

namespace App\Services\Auth;

use App\Models\Identity\User;
use RuntimeException;

/**
 * Shared Prestige bearer token for category and certificate integrations.
 */
class TokenService
{
    public function __construct(private readonly SsoAuthService $sso) {}

    public function getToken(): string
    {
        return 'Bearer '.$this->sso->ensureValidAccessToken($this->resolveUser());
    }

    public function forceRefresh(): string
    {
        $user = $this->resolveUser();
        $refresh = $user->refresh_token;
        if (! $refresh) {
            throw new RuntimeException('Token Prestige elearning tidak tersedia. Login ulang sebagai elearning.');
        }

        $refreshed = $this->sso->refreshGrant($refresh);
        $this->sso->persistTokens($user, $refreshed);

        $token = $user->fresh()->access_token;
        if (! $token) {
            throw new RuntimeException('Token Prestige elearning tidak tersedia. Login ulang sebagai elearning.');
        }

        return 'Bearer '.$token;
    }

    protected function resolveUser(): User
    {
        $username = (string) config('services.sso.admin_username', 'elearning');
        $user = User::query()
            ->where('nip', $username)
            ->where('auth_source', User::AUTH_SOURCE_PRESTIGE)
            ->whereHas('roles', fn ($roles) => $roles->where('name', 'super_admin')->where('guard_name', 'web'))
            ->first();

        if (! $user || (! $user->access_token && ! $user->refresh_token)) {
            throw new RuntimeException('Token Prestige elearning belum tersedia. Login ulang sebagai elearning.');
        }

        return $user;
    }
}
