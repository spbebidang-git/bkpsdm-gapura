<?php

namespace App\Repositories\Course\Activity\Contracts;

use App\Models\Course\Activity\Literature;
use App\Repositories\Contracts\BaseRepositoryInterface;

interface LiteratureRepositoryInterface extends BaseRepositoryInterface
{
    public function findByActivity(int|string $activityId): ?Literature;
}
