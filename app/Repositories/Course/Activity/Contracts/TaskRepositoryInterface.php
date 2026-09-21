<?php

namespace App\Repositories\Course\Activity\Contracts;

use App\Models\Course\Activity\Task;
use App\Repositories\Contracts\BaseRepositoryInterface;

interface TaskRepositoryInterface extends BaseRepositoryInterface
{
    public function findByActivity(int|string $activityId): ?Task;
}
