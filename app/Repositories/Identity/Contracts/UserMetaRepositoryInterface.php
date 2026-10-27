<?php

namespace App\Repositories\Identity\Contracts;

use App\Models\Identity\UserMeta;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface UserMetaRepositoryInterface extends BaseRepositoryInterface
{
    public function findByUserAndKey(int|string $userId, string $key): ?UserMeta;

    public function getByUser(int|string $userId): Collection;

    public function findWithUserByKeyAndValue(string $key, string $value): ?UserMeta;

    public function findWithUserByUserAndKey(int|string $userId, string $key): ?UserMeta;

    public function getWithUserByKey(string $key): Collection;

    public function put(int|string $userId, string $key, string $value): UserMeta;
}
