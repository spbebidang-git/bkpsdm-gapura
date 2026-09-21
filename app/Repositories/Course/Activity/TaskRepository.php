<?php

namespace App\Repositories\Course\Activity;

use App\Models\Course\Activity\Task;
use App\Repositories\Course\Activity\Contracts\TaskRepositoryInterface;
use App\Repositories\EloquentRepository;

class TaskRepository extends EloquentRepository implements TaskRepositoryInterface
{
    public function __construct(Task $model)
    {
        parent::__construct($model);
    }

    public function findByActivity(int|string $activityId): ?Task
    {
        return $this->query()->where('activity_id', $activityId)->first();
    }
}
