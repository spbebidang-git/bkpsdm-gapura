<?php

namespace App\Repositories\Identity\Contracts;

use App\Models\Identity\User;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function findByNip(string $nip): ?User;

    public function findByEmail(string $email): ?User;

    public function getByRole(string $role, array $relations = []): Collection;

    public function queryByRole(string $role, array $relations = []): Builder;

    public function getManagementUsers(): Collection;

    public function syncRoles(User $user, array $roles): void;

    public function nipExistsExcept(string $nip, string $userId): bool;

    public function emailExistsExcept(string $email, string $userId): bool;

    public function assignRole(User $user, string $role): void;

    public function clearLegacyTokens(User $user): void;

    public function trainersWithoutProfile(): Collection;

    public function eligibleTrainerUsers(): Collection;

    public function lock(int|string $id): ?User;

    public function putMeta(User $user, string $key, string $value): void;

    public function removeRole(User $user, string $role): void;
}
