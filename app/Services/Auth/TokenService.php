<?php

namespace App\Services\Auth;

use App\Models\Identity\User;
use App\Repositories\Identity\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * SSO bearer token for server-side calls (cert sync, category sync, artisan).
 * Uses logged-in user tokens — no shared admin password / API key.
 */
class TokenService
{
    public function __construct(
        private readonly SsoAuthService $sso,
        private readonly UserRepositoryInterface $users,
    ) {}

    public function getToken(): string
    {
        return 'Bearer '.$this->sso->ensureValidAccessToken($this->resolveUser());
    }

    public function forceRefresh(): string
    {
        $user = $this->resolveUser();
        $refresh = $user->refresh_token;
        if (! $refresh) {
            throw new RuntimeException('Sesi SSO habis. Login admin ulang.');
        }

        $refreshed = $this->sso->refreshGrant($refresh);
        $this->sso->persistTokens($user, $refreshed);

        $token = $user->fresh()->access_token;
        if (! $token) {
            throw new RuntimeException('Sesi SSO habis. Login admin ulang.');
        }

        return 'Bearer '.$token;
    }

    protected function resolveUser(): User
    {
        $user = Auth::user();
        if ($user instanceof User && ($user->access_token || $user->refresh_token)) {
            return $user;
        }

        // CLI / queue: reuse last Admin who still has SSO refresh token
        $admin = $this->users->getByRole('Admin')
            ->filter(fn (User $user) => $user->refresh_token !== null)
            ->sortByDesc('token_expires_at')
            ->first();

        if (! $admin) {
            throw new RuntimeException('Tidak ada token SSO admin. Login sebagai Admin dulu.');
        }

        return $admin;
    }
}
