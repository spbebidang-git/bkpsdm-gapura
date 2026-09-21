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
}
