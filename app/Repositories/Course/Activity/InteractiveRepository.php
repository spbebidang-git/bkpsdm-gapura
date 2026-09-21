<?php

namespace App\Repositories\Course\Activity;

use App\Models\Course\Activity\Interactive;
use App\Repositories\Course\Activity\Contracts\InteractiveRepositoryInterface;
use App\Repositories\EloquentRepository;

class InteractiveRepository extends EloquentRepository implements InteractiveRepositoryInterface
{
    public function __construct(Interactive $model)
    {
        parent::__construct($model);
    }

    public function findByActivity(int|string $activityId): ?Interactive
    {
        return $this->query()->where('activity_id', $activityId)->first();
    }
}
