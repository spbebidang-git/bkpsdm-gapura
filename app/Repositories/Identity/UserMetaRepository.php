<?php

namespace App\Repositories\Identity;

use App\Models\Identity\UserMeta;
use App\Repositories\EloquentRepository;
use App\Repositories\Identity\Contracts\UserMetaRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class UserMetaRepository extends EloquentRepository implements UserMetaRepositoryInterface
{
    public function __construct(UserMeta $model)
    {
        parent::__construct($model);
    }

    public function findByUserAndKey(int|string $userId, string $key): ?UserMeta
    {
        return $this->query()->where('user_id', $userId)->where('key', $key)->first();
    }

    public function getByUser(int|string $userId): Collection
    {
        return $this->query()->where('user_id', $userId)->get();
    }
}
