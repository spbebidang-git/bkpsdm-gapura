<?php

namespace App\Repositories\Identity;

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
}
