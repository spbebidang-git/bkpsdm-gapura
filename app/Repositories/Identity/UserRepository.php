<?php

namespace App\Repositories\Identity;

use App\Models\Identity\User;
use App\Repositories\EloquentRepository;
use App\Repositories\Identity\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Course\Trainer;

class UserRepository extends EloquentRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByNip(string $nip): ?User
    {
        return $this->query()->where('nip', $nip)->first();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->query()->where('email', $email)->first();
    }

    public function getByRole(string $role, array $relations = []): Collection
    {
        return $this->queryByRole($role, $relations)->get();
    }

    public function queryByRole(string $role, array $relations = []): Builder
    {
        return $this->query()->with($relations)->role($role)->orderBy('name');
    }

    public function getManagementUsers(): Collection
    {
        return $this->query()->role(['super_admin', 'administrator'])->with('roles')->orderBy('id')->get();
    }

    public function syncRoles(User $user, array $roles): void
    {
        $user->syncRoles($roles);
    }

    public function nipExistsExcept(string $nip, string $userId): bool
    {
        return $this->query()->where('nip', $nip)->where('id', '!=', $userId)->exists();
    }

    public function emailExistsExcept(string $email, string $userId): bool
    {
        return $this->query()->where('email', $email)->where('id', '!=', $userId)->exists();
    }

    public function assignRole(User $user, string $role): void
    {
        $user->assignRole($role);
    }

    public function clearLegacyTokens(User $user): void
    {
        $user->forceFill(['access_token' => null, 'refresh_token' => null, 'token_expires_at' => null])->save();
    }

    public function trainersWithoutProfile(): Collection
    {
        return $this->query()->role('trainer')
            ->whereNotIn('id', Trainer::query()->whereNotNull('user_id')->select('user_id'))->get();
    }

    public function eligibleTrainerUsers(): Collection
    {
        return $this->query()->where(fn ($query) => $query
                ->where('auth_source', User::AUTH_SOURCE_LOCAL)
                ->orWhereHas('opd', fn ($opd) => $opd->where('is_active', true)))
            ->whereNotIn('id', Trainer::query()->whereNotNull('user_id')->select('user_id'))
            ->whereDoesntHave('roles', fn ($query) => $query->whereIn('name', ['trainer', 'super_admin']))
            ->with(['opd', 'metas'])->orderBy('name')->get();
    }

    public function lock(int|string $id): ?User
    {
        return $this->query()->whereKey($id)->lockForUpdate()->first();
    }

    public function putMeta(User $user, string $key, string $value): void
    {
        $user->metas()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public function removeRole(User $user, string $role): void
    {
        $user->removeRole($role);
    }
}
