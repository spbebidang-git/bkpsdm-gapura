<?php

namespace App\Repositories\Course;

use App\Models\Course\Course;
use App\Repositories\Course\Contracts\CourseRepositoryInterface;
use App\Repositories\EloquentRepository;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;

class CourseRepository extends EloquentRepository implements CourseRepositoryInterface
{
    public function __construct(Course $model)
    {
        parent::__construct($model);
    }

    public function findBySlug(string $slug, array $relations = []): ?Course
    {
        return $this->query()->with($relations)->where('slug', $slug)->first();
    }

    public function findPublishedBySlug(string $slug, array $relations = []): ?Course
    {
        return $this->query()
            ->with($relations)
            ->where('slug', $slug)
            ->where('status', 'publish')
            ->first();
    }

    public function getPublishedAvailable(?DateTimeInterface $at = null, ?int $limit = null): Collection
    {
        $now = $at ? Carbon::instance($at) : now();
        $date = $now->toDateString();
        $time = $now->format('H:i:s');

        $query = $this->query()
            ->with(['category', 'employeeStatus'])
            ->where('status', 'publish')
            ->where(function ($query) use ($date, $time) {
                $query->where('access_mode', '!=', 'scheduled')
                    ->orWhereHas('schedules', function ($scheduleQuery) use ($date, $time) {
                        $scheduleQuery->where('date', '>', $date)
                            ->orWhere(function ($todayQuery) use ($date, $time) {
                                $todayQuery->whereDate('date', $date)
                                    ->whereTime('time_to', '>=', $time);
                            });
                    });
            })
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function getPublishedActiveAt(DateTimeInterface $at, int $limit = 8): Collection
    {
        $now = Carbon::instance($at);

        return $this->query()
            ->with('category')
            ->where('status', 'publish')
            ->where(function ($query) use ($now) {
                $query->where('access_mode', '!=', 'scheduled')
                    ->orWhereHas('schedules', function ($scheduleQuery) use ($now) {
                        $scheduleQuery->whereDate('date', $now->toDateString())
                            ->whereTime('time_from', '<=', $now->format('H:i:s'))
                            ->whereTime('time_to', '>=', $now->format('H:i:s'));
                    });
            })
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function getForInstructor(int|string|null $instructorId = null): Collection
    {
        return $this->query()->with(['category', 'employeeStatus'])->get();
    }

    public function getByParticipant(int|string $participantId): Collection
    {
        return $this->query()
            ->whereHas('enrollments', fn ($query) => $query->where('participant_id', $participantId))
            ->with([
                'category',
                'activities',
                'enrollments' => fn ($query) => $query->where('participant_id', $participantId),
                'learns' => fn ($query) => $query->where('participant_id', $participantId),
            ])
            ->get();
    }

    public function syncEmployeeStatuses(Course $course, array $employeeStatusIds): void
    {
        $course->employeeStatus()->sync($employeeStatusIds);
    }

    public function syncTrainers(Course $course, array $trainerIds): void
    {
        $course->trainers()->sync($trainerIds);
    }
}
