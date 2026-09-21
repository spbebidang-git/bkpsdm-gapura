<?php

namespace App\Services\Auth;

use App\Models\Identity\User;
use App\Repositories\Identity\Contracts\UserRepositoryInterface;
use App\Services\Identity\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SsoAuthService
{
    public function __construct(
        private readonly UserService $userService,
        private readonly UserRepositoryInterface $users,
    ) {}

    /**
     * Full SSO login every form submit: password grant → nip data → upsert user + tokens.
     * Session cookie handles “stay logged in”; access_token only for API calls.
     *
     * @throws RuntimeException on SSO/auth failure
     */
    public function loginOrProvision(string $nip, string $password): User
    {
        $nip = normalize_nip($nip) ?? trim($nip);
        $tokenResponse = $this->passwordGrant($nip, $password);

        $accessToken = $tokenResponse['access_token'] ?? null;
        if (! $accessToken) {
            throw new RuntimeException('SSO tidak mengembalikan access_token.');
        }

        $nipData = $this->fetchNipData($accessToken);
        $user = $this->resolveOrCreateUser($nip, $nipData);

        $this->persistTokens($user, $tokenResponse);
        $this->userService->storeUserMeta($user->id, $nipData, $nip);

        $this->users->update($user, [
            'name' => $nipData['fv_nama'] ?? $user->name,
            'nip' => $nip,
        ]);

        $this->syncRolesAfterLogin($user, $nip);

        return $user->fresh();
    }

    /**
     * Sole-admin rule: only SSO_ADMIN_NIP (default elearning) holds Admin.
     * Everyone else → Participant (Admin stripped if present).
     */
    protected function syncRolesAfterLogin(User $user, string $nip): void
    {
        $adminNip = strtolower((string) config('services.sso.admin_nip', 'elearning'));

        if (strtolower($nip) === $adminNip) {
            $user->syncRoles(['Admin']);

            $this->users->getByRole('Admin')
                ->reject(fn (User $other) => $other->is($user))
                ->each(fn (User $other) => $other->removeRole('Admin'));

            return;
        }

        if ($user->hasRole('Admin')) {
            $user->removeRole('Admin');
        }

        if (! $user->hasAnyRole(['Participant', 'Instructor'])) {
            $user->assignRole('Participant');
        }
    }

    /**
     * @return array{access_token?: string, refresh_token?: string, expires_in?: int}
     */
    public function passwordGrant(string $nip, string $password): array
    {
        $this->assertSsoConfigured();

        $response = Http::timeout(8)
            ->retry(2, 150)
            ->asForm()
            ->post(rtrim(config('services.sso.base_url'), '/').'/oauth/token', [
                'username' => $nip,
                'password' => $password,
                'grant_type' => 'password',
                'client_id' => config('services.sso.client_id'),
                'client_secret' => config('services.sso.client_secret'),
            ]);

        if (! $response->successful()) {
            Log::warning('SSO password grant failed', [
                'nip' => $nip,
                'status' => $response->status(),
            ]);
            throw new RuntimeException('Kredensial SSO tidak valid.');
        }

        $json = $response->json();
        if (! is_array($json) || empty($json['access_token'])) {
            throw new RuntimeException('Respons SSO tidak valid.');
        }

        return $json;
    }

    /**
     * @return array{access_token?: string, refresh_token?: string, expires_in?: int}
     */
    public function refreshGrant(string $refreshToken): array
    {
        $this->assertSsoConfigured();

        $response = Http::timeout(8)
            ->retry(2, 150)
            ->asForm()
            ->post(rtrim(config('services.sso.base_url'), '/').'/oauth/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken,
                'client_id' => config('services.sso.client_id'),
                'client_secret' => config('services.sso.client_secret'),
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Refresh token SSO gagal.');
        }

        $json = $response->json();
        if (! is_array($json) || empty($json['access_token'])) {
            throw new RuntimeException('Respons refresh SSO tidak valid.');
        }

        return $json;
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchNipData(string $accessToken): array
    {
        $response = Http::timeout(8)
            ->withToken($accessToken)
            ->acceptJson()
            ->post(rtrim(config('services.sso.base_url'), '/').'/api/get_nip_data');

        if (! $response->successful()) {
            Log::warning('SSO get_nip_data failed', ['status' => $response->status()]);
            throw new RuntimeException('Gagal mengambil data NIP dari SSO.');
        }

        $data = $response->json();
        if (! is_array($data)) {
            throw new RuntimeException('Data NIP SSO kosong.');
        }

        return $data;
    }

    public function persistTokens(User $user, array $tokenResponse): void
    {
        $this->users->update($user, [
            'access_token' => $tokenResponse['access_token'] ?? null,
            'refresh_token' => $tokenResponse['refresh_token'] ?? $user->refresh_token,
            'token_expires_at' => isset($tokenResponse['expires_in'])
                ? now()->addSeconds((int) $tokenResponse['expires_in'])
                : null,
        ]);
    }

    public function clearTokens(User $user): void
    {
        $this->users->update($user, [
            'access_token' => null,
            'refresh_token' => null,
            'token_expires_at' => null,
        ]);
    }

    /**
     * Return raw (decrypted via cast) access token, refreshing if needed.
     *
     * @throws RuntimeException
     */
    public function ensureValidAccessToken(User $user): string
    {
        $token = $user->access_token;
        $expiresAt = $user->token_expires_at;

        if ($token && $expiresAt && $expiresAt->isFuture()) {
            return $token;
        }

        $refresh = $user->refresh_token;
        if (! $refresh) {
            throw new RuntimeException('Sesi SSO habis. Login ulang.');
        }

        $refreshed = $this->refreshGrant($refresh);
        $this->persistTokens($user, $refreshed);

        $fresh = $user->fresh()->access_token;
        if (! $fresh) {
            throw new RuntimeException('Sesi SSO habis. Login ulang.');
        }

        return $fresh;
    }

    protected function assertSsoConfigured(): void
    {
        $base = config('services.sso.base_url');
        if (! is_string($base) || $base === '' || ! preg_match('#^https?://#i', $base)) {
            throw new RuntimeException('SSO belum dikonfigurasi (SSO_BASE_URL kosong/invalid di .env).');
        }
        foreach (['client_id', 'client_secret'] as $key) {
            if (! filled(config("services.sso.{$key}"))) {
                throw new RuntimeException("SSO belum dikonfigurasi ({$key} kosong di .env).");
            }
        }
    }

    /**
     * @param  array<string, mixed>  $nipData
     */
    protected function resolveOrCreateUser(string $nip, array $nipData): User
    {
        $meta = find_user_by_nip($nip);
        if ($meta?->user) {
            return $meta->user;
        }

        $byNip = $this->users->findByNip($nip);
        if ($byNip) {
            return $byNip;
        }

        $email = filled($nipData['fv_email'] ?? null)
            ? trim($nipData['fv_email'])
            : "{$nip}@gresikkab.go.id";

        $existingEmail = $this->users->findByEmail($email);
        if ($existingEmail) {
            return $existingEmail;
        }

        return DB::transaction(function () use ($nip, $nipData, $email) {
            $user = $this->users->create([
                'name' => $nipData['fv_nama'] ?? "User {$nip}",
                'email' => $email,
                'nip' => $nip,
            ]);

            $user->assignRole('Participant');

            return $user;
        });
    }
}
