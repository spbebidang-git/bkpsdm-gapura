<?php

namespace App\Repositories\Identity;

use App\Models\Course\Trainer;
use App\Models\Identity\User;
use App\Repositories\EloquentRepository;
use App\Repositories\Identity\Contracts\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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

    public function deleteMeta(User $user, string $key): void
    {
        $user->metas()->where('key', $key)->delete();
    }

    public function hasRoles(User $user): bool
    {
        return $user->roles()->exists();
    }

    public function loadParticipant(User $user): User
    {
        return $user->load(['metas', 'employeeStatus']);
    }

    public function orderParticipantMeta(Builder $query, string $key, string $order): void
    {
        $dir = strtolower($order) === 'desc' ? 'desc' : 'asc';
        if ($key === 'status_pegawai') {
            $query->orderByRaw('coalesce((select title from employee_status where employee_status.id = users.employee_status_id limit 1), (select value from user_meta where user_meta.user_id = users.id and user_meta.key = ? limit 1)) '.$dir, [$key]);
        } else {
            $query->orderByRaw('(select value from user_meta where user_meta.user_id = users.id and user_meta.key = ? limit 1) '.$dir, [$key]);
        }
    }

    public function adminParticipants(): Builder
    {
        return $this->queryByRole('participant', ['roles', 'metas', 'employeeStatus', 'opd'])->orderBy('id');
    }

    public function notificationParticipants()
    {
        return $this->queryByRole('participant')->get(['id', 'name', 'nip']);
    }

    public function isParticipant(string $id): bool
    {
        return $this->queryByRole('participant')->whereKey($id)->exists();
    }
}
