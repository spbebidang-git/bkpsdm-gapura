<?php

namespace App\Repositories\Course\Activity\Contracts;

use App\Models\Course\Activity\Interactive;
use App\Repositories\Contracts\BaseRepositoryInterface;

interface InteractiveRepositoryInterface extends BaseRepositoryInterface
{
    public function findByActivity(int|string $activityId): ?Interactive;
}
