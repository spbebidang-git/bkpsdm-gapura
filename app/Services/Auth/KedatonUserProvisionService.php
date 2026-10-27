<?php

namespace App\Services\Auth;

use App\Models\Identity\User;
use App\Repositories\Identity\Contracts\UserRepositoryInterface;
use App\Services\Identity\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class KedatonUserProvisionService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
        private readonly UserService $userService,
    ) {}

    public function provisionOrSync(string $nip, array $data): ?User
    {
        $userMeta = find_user_by_nip($nip);
        if ($userMeta?->user) {
            $user = $userMeta->user;
            if (! $user->usesSsoAuthentication()) {
                Log::warning('Penautan akun lokal melalui Kedaton ditolak.', ['event' => 'auth.kedaton.local_link_denied', 'user_id' => $user->id]);
                return null;
            }
            if ($this->users->nipExistsExcept($nip, (string) $user->id)) {
                Log::warning('Konflik akun identitas Kedaton ditemukan.', ['event' => 'auth.kedaton.account_conflict', 'user_id' => $user->id]);
                return null;
            }
            return $this->sync($user, $nip, $data);
        }

        $userByNip = $this->users->findByNip($nip);
        if ($userByNip) {
            if (! $userByNip->usesSsoAuthentication()) {
                Log::warning('Penautan akun lokal melalui identitas Kedaton ditolak.', ['event' => 'auth.kedaton.local_identity_link_denied', 'user_id' => $userByNip->id]);
                return null;
            }
            return $this->sync($userByNip, $nip, $data);
        }

        $email = trim((string) ($data['fv_email'] ?? '')) ?: $nip.'@kedaton.invalid';
        $existingUser = $this->users->findByEmail($email);
        if ($existingUser) {
            if (! $existingUser->usesSsoAuthentication()
                || (filled($existingUser->nip) && $existingUser->nip !== $nip)) {
                Log::warning('Penautan akun Kedaton dengan identitas berbeda ditolak.', ['event' => 'auth.kedaton.email_link_denied', 'user_id' => $existingUser->id]);
                return null;
            }
            return $this->sync($existingUser, $nip, $data);
        }

        return DB::transaction(function () use ($data, $nip, $email): User {
            $user = $this->users->create([
                'name' => $data['fv_nama'] ?? 'Pengguna Kedaton',
                'email' => $email, 'nip' => $nip,
                'auth_source' => User::AUTH_SOURCE_SSO,
                'password' => Str::password(64),
            ]);
            $this->users->assignRole($user, 'participant');
            $this->userService->storeUserMeta($user->id, $data, $nip);
            return $user;
        });
    }

    public function ensureRole(User $user): void
    {
        if (! $user->hasAnyRole(['administrator', 'trainer', 'participant'])) {
            $this->users->assignRole($user, 'participant');
        }
    }

    public function clearLegacyTokens(User $user): void
    {
        $this->users->clearLegacyTokens($user);
    }

    private function sync(User $user, string $nip, array $data): User
    {
        return DB::transaction(function () use ($user, $data, $nip): User {
            $attributes = ['name' => $data['fv_nama'] ?? $user->name, 'nip' => $nip];
            $email = trim((string) ($data['fv_email'] ?? ''));
            if ($email !== '' && ! $this->users->emailExistsExcept($email, (string) $user->id)) {
                $attributes['email'] = $email;
            }
            $this->users->update($user, $attributes);
            $this->userService->storeUserMeta($user->id, $data, $nip);
            return $user->fresh();
        });
    }
}
