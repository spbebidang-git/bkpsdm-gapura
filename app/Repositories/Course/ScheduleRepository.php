<?php

namespace App\Repositories\Course;

use App\Models\Course\Schedule;
use App\Repositories\Course\Contracts\ScheduleRepositoryInterface;
use App\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ScheduleRepository extends EloquentRepository implements ScheduleRepositoryInterface
{
    public function __construct(Schedule $model)
    {
        parent::__construct($model);
    }

    public function getByCourse(int|string $courseId): Collection
    {
        return $this->query()->where('course_id', $courseId)->orderBy('date')->get();
    }

    public function getByParticipant(int|string $participantId): Collection
    {
        return $this->query()
            ->whereRelation('participants', 'participant_id', $participantId)
            ->with([
                'course.opds', 'course.employeeStatus',
                'course.learns' => fn ($query) => $query->where('participant_id', $participantId),
                'course.activities',
            ])
            ->get();
    }

    public function findByCourse(int|string $courseId, int|string $scheduleId): ?Schedule
    {
        return $this->query()->where('course_id', $courseId)->whereKey($scheduleId)->first();
    }

    public function deleteMissingForCourse(int|string $courseId, array $scheduleIds): int
    {
        return $this->query()->where('course_id', $courseId)->whereNotIn('id', $scheduleIds)->delete();
    }

    public function lock(int|string $id): Schedule
    {
        return $this->query()->whereKey($id)->lockForUpdate()->firstOrFail();
    }

    public function participantCount(int|string $scheduleId): int
    {
        return DB::table('enrollments')->where('schedule_id', $scheduleId)
            ->whereNull('deleted_at')->distinct()->count('participant_id');
    }

    public function firstForParticipantAndCourse(int|string $courseId, int|string $participantId): ?Schedule
    {
        return $this->query()->where('course_id', $courseId)
            ->whereHas('participants', fn ($query) => $query->where('users.id', $participantId))->first();
    }

    public function firstForCourse(int|string $courseId): ?Schedule
    {
        return $this->query()->where('course_id', $courseId)->first();
    }

    public function participantHasSchedule(int|string $scheduleId, int|string $participantId): bool
    {
        return $this->query()->whereKey($scheduleId)
            ->whereHas('participants', fn ($query) => $query->where('users.id', $participantId))->exists();
    }
}
