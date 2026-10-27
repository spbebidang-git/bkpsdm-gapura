<?php

namespace App\Repositories\Course\Activity\Assessment\Contracts;

use App\Models\Course\Activity\Assessment\Question;
use App\Repositories\Contracts\BaseRepositoryInterface;

interface QuestionRepositoryInterface extends BaseRepositoryInterface
{
    public function findByActivity(int|string $activityId, array $relations = []): ?Question;

    public function findActivityVariant(int|string $activityId, int|string $variantId): ?object;

    public function lockByActivity(int|string $activityId): Question;
}
