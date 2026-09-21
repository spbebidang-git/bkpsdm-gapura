<?php

namespace App\Repositories\Course\Activity;

use App\Models\Course\Activity\Literature;
use App\Repositories\Course\Activity\Contracts\LiteratureRepositoryInterface;
use App\Repositories\EloquentRepository;

class LiteratureRepository extends EloquentRepository implements LiteratureRepositoryInterface
{
    public function __construct(Literature $model)
    {
        parent::__construct($model);
    }

    public function findByActivity(int|string $activityId): ?Literature
    {
        return $this->query()->where('activity_id', $activityId)->first();
    }
}
