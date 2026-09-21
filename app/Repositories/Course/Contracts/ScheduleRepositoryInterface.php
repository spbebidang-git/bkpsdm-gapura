<?php

namespace App\Repositories\Course\Contracts;

use App\Models\Course\Schedule;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface ScheduleRepositoryInterface extends BaseRepositoryInterface
{
    public function getByCourse(int|string $courseId): Collection;

    public function getByParticipant(int|string $participantId): Collection;

    public function findByCourse(int|string $courseId, int|string $scheduleId): ?Schedule;

    public function deleteMissingForCourse(int|string $courseId, array $scheduleIds): int;
}
