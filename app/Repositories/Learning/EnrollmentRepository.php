<?php

namespace App\Repositories\Learning;

use App\Models\Learning\Enrollment;
use App\Repositories\EloquentRepository;
use App\Repositories\Learning\Contracts\EnrollmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class EnrollmentRepository extends EloquentRepository implements EnrollmentRepositoryInterface
{
    public function __construct(Enrollment $model)
    {
        parent::__construct($model);
    }

    public function findByParticipantAndCourse(int|string $participantId, int|string $courseId): ?Enrollment
    {
        return $this->query()
            ->where('participant_id', $participantId)
            ->where('course_id', $courseId)
            ->first();
    }

    public function findByParticipantCourseAndSchedule(
        int|string $participantId,
        int|string $courseId,
        int|string|null $scheduleId,
    ): ?Enrollment {
        return $this->query()
            ->where('participant_id', $participantId)
            ->where('course_id', $courseId)
            ->where('schedule_id', $scheduleId)
            ->first();
    }

    public function getActiveByParticipant(int|string $participantId): Collection
    {
        return $this->query()
            ->with(['course', 'schedule'])
            ->where('participant_id', $participantId)
            ->where('status', 'active')
            ->where('access_starts_at', '<=', now())
            ->where(function ($query) {
                $query->whereNull('access_ends_at')->orWhere('access_ends_at', '>=', now());
            })
            ->get();
    }
}
