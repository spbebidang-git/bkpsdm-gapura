<?php

namespace App\Repositories\Learning\Contracts;

use App\Models\Learning\Enrollment;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface EnrollmentRepositoryInterface extends BaseRepositoryInterface
{
    public function findByParticipantAndCourse(int|string $participantId, int|string $courseId): ?Enrollment;

    public function findByParticipantCourseAndSchedule(
        int|string $participantId,
        int|string $courseId,
        int|string|null $scheduleId,
    ): ?Enrollment;

    public function getActiveByParticipant(int|string $participantId): Collection;
}
