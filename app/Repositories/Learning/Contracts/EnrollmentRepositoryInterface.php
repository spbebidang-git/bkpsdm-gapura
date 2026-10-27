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

    public function expiredIds(int|string $participantId, int|string|null $courseId = null): array;

    public function lock(int|string $id): ?Enrollment;

    public function lockByParticipantAndCourse(int|string $participantId, int|string $courseId): ?Enrollment;

    public function latestArchived(int|string $participantId, int|string $courseId): ?Enrollment;

    public function hasArchived(int|string $participantId, int|string $courseId): bool;

    public function previousArchivedForSchedule(int|string $participantId, int|string $courseId, int|string|null $scheduleId): ?Enrollment;

    public function withLearnByIds(array $ids): Collection;
}
