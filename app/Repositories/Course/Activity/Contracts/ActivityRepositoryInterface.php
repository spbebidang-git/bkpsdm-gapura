<?php

namespace App\Repositories\Course\Activity\Contracts;

use App\Models\Course\Activity\Activity;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface ActivityRepositoryInterface extends BaseRepositoryInterface
{
    public function getByCourse(int|string $courseId, array $relations = []): Collection;

    public function getByCourseAndTypes(int|string $courseId, array $types): Collection;

    public function getNext(int|string $courseId, int|string $activityId): ?Activity;

    public function getPrevious(int|string $courseId, int|string $activityId): ?Activity;
}
