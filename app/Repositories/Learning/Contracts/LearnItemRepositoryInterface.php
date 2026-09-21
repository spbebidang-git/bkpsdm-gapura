<?php

namespace App\Repositories\Learning\Contracts;

use App\Models\Learning\LearnItem;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface LearnItemRepositoryInterface extends BaseRepositoryInterface
{
    public function findByLearnAndActivity(int|string $learnId, int|string $activityId): ?LearnItem;

    public function getByLearn(int|string $learnId): Collection;

    public function updateOrCreateForLearnAndActivity(
        int|string $learnId,
        int|string $activityId,
        array $attributes,
    ): LearnItem;
}
