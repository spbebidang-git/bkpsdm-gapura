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

    public function expiredIds(int|string $participantId, int|string|null $courseId = null): array
    {
        return $this->query()->where('participant_id', $participantId)
            ->when($courseId, fn ($query) => $query->where('course_id', $courseId))
            ->whereIn('status', ['active', 'expired'])
            ->whereNotNull('access_ends_at')->where('access_ends_at', '<', now())
            ->whereDoesntHave('learn', fn ($query) => $query->whereNotNull('finished_at'))
            ->pluck('id')->all();
    }

    public function lock(int|string $id): ?Enrollment
    {
        return $this->query()->whereKey($id)->lockForUpdate()->first();
    }

    public function lockByParticipantAndCourse(int|string $participantId, int|string $courseId): ?Enrollment
    {
        return $this->query()->where('participant_id', $participantId)
            ->where('course_id', $courseId)->lockForUpdate()->first();
    }

    public function latestArchived(int|string $participantId, int|string $courseId): ?Enrollment
    {
        return $this->model->newQuery()->withTrashed()->where('participant_id', $participantId)
            ->where('course_id', $courseId)->whereNotNull('deleted_at')->latest('deleted_at')->first();
    }

    public function hasArchived(int|string $participantId, int|string $courseId): bool
    {
        return $this->model->newQuery()->withTrashed()->where('participant_id', $participantId)
            ->where('course_id', $courseId)->whereNotNull('deleted_at')->exists();
    }

    public function previousArchivedForSchedule(int|string $participantId, int|string $courseId, int|string|null $scheduleId): ?Enrollment
    {
        return $this->model->newQuery()->withTrashed()->where('participant_id', $participantId)
            ->where('course_id', $courseId)->where('schedule_id', $scheduleId)
            ->whereNotNull('deleted_at')->latest('deleted_at')->first();
    }

    public function withLearnByIds(array $ids): Collection
    {
        return $this->query()->with('learn')->whereIn('id', $ids)->get();
    }
}
