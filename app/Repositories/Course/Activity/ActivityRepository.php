<?php

namespace App\Repositories\Course\Activity;

use App\Models\Course\Activity\Activity;
use App\Repositories\Course\Activity\Contracts\ActivityRepositoryInterface;
use App\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Collection;

class ActivityRepository extends EloquentRepository implements ActivityRepositoryInterface
{
    public function __construct(Activity $model)
    {
        parent::__construct($model);
    }

    public function getByCourse(int|string $courseId, array $relations = []): Collection
    {
        return $this->query()->with($relations)->where('course_id', $courseId)->orderBy('sort_order')->orderBy('id')->get();
    }

    public function getByCourseAndTypes(int|string $courseId, array $types): Collection
    {
        return $this->query()
            ->where('course_id', $courseId)
            ->whereIn('type', $types)
            ->orderBy('sort_order')->orderBy('id')
            ->get();
    }

    public function getNext(int|string $courseId, int|string $activityId): ?Activity
    {
        return $this->query()
            ->where('course_id', $courseId)
            ->orderBy('sort_order')->orderBy('id')->get()
            ->skipUntil(fn (Activity $activity) => (string) $activity->id === (string) $activityId)
            ->skip(1)->first();
    }

    public function getPrevious(int|string $courseId, int|string $activityId): ?Activity
    {
        return $this->query()
            ->where('course_id', $courseId)
            ->orderBy('sort_order')->orderBy('id')->get()
            ->takeUntil(fn (Activity $activity) => (string) $activity->id === (string) $activityId)
            ->last();
    }
}
