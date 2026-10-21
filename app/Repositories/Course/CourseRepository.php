<?php

namespace App\Repositories\Course;

use App\Models\Course\Course;
use App\Models\Course\Trainer;
use App\Repositories\Course\Contracts\CourseRepositoryInterface;
use App\Repositories\EloquentRepository;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use App\Models\Identity\User;

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

    public function findVisibleBySlug(string $slug, ?User $user = null, array $relations = []): ?Course
    {
        return $this->query()->with($relations)->where('slug', $slug)
            ->where(fn ($q) => app(\App\Services\Course\CourseAudience::class)->viewable($q, $user))->first();
    }

    public function getPublishedAvailable(?DateTimeInterface $at = null, ?int $limit = null, array $filters = [], ?User $user = null): Collection
    {
        $now = $at ? Carbon::instance($at) : now();
        $date = $now->toDateString();
        $time = $now->format('H:i:s');

        $query = $this->query()
            ->with(['category', 'employeeStatus', 'opds'])
            ->withCount([
                'learns as total_passed_count' => fn ($query) => $query->whereNotNull('finished_at'),
                'reviews as rating_count',
            ])
            ->withSum('reviews as rating_total', 'star')
            ->select('courses.*')
            ->selectSub(function ($query) {
                $query->from('schedule_user')
                    ->join('schedules', 'schedules.id', '=', 'schedule_user.schedule_id')
                    ->whereColumn('schedules.course_id', 'courses.id')
                    ->whereNull('schedules.deleted_at')
                    ->selectRaw('count(*)');
            }, 'total_participant_count')
            ->where('status', 'publish')
            ->where(fn ($query) => $this->applyAudience($query, $user))
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
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('title', 'like', "%{$search}%"))
            ->when($filters['category'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['employee_status'] ?? null, fn ($query, $employeeStatus) => $query->whereHas(
                'employeeStatus',
                fn ($statusQuery) => $statusQuery->where('title', $employeeStatus)
            ))
            ->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function getFeaturedPublishedCourses(DateTimeInterface $at, int $limit = 6, ?User $user = null): Collection
    {
        $now = Carbon::instance($at);

        return $this->query()
            ->with(['category', 'employeeStatus', 'opds'])
            ->withCount([
                'learns as total_passed_count' => fn ($query) => $query->whereNotNull('finished_at'),
                'reviews as rating_count',
            ])
            ->withSum('reviews as rating_total', 'star')
            ->select('courses.*')
            ->selectSub(function ($query) {
                $query->from('schedule_user')
                    ->join('schedules', 'schedules.id', '=', 'schedule_user.schedule_id')
                    ->whereColumn('schedules.course_id', 'courses.id')
                    ->whereNull('schedules.deleted_at')
                    ->selectRaw('count(*)');
            }, 'total_participant_count')
            ->where('status', 'publish')
            ->where(fn ($query) => $this->applyAudience($query, $user))
            ->where(function ($query) use ($now) {
                $query->where('access_mode', '!=', 'scheduled')
                    ->orWhereHas('schedules', function ($scheduleQuery) use ($now) {
                        $scheduleQuery->where('date', '>', $now->toDateString())
                            ->orWhere(function ($todayQuery) use ($now) {
                                $todayQuery->whereDate('date', $now->toDateString())
                                    ->whereTime('time_to', '>=', $now->format('H:i:s'));
                            });
                    });
            })
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }

    public function getForInstructor(int|string|null $instructorId = null): Collection
    {
        $query = $this->query()->with(['category', 'employeeStatus', 'opds', 'modifier:id,name'])->withCount('schedules');

        if ($instructorId !== null) {
            $query->whereHas('trainers', fn ($trainer) => $trainer->where('user_id', $instructorId));
            if (! Trainer::activeOpdIdsForUser($instructorId)) $query->whereRaw('1 = 0');
        }

        return $query->orderByDesc('courses.updated_at')->orderByDesc('courses.id')->get();
    }

    private function applyAudience($query, ?User $user): void
    {
        app(\App\Services\Course\CourseAudience::class)->visible($query, $user);
    }

    public function getByParticipant(int|string $participantId): Collection
    {
        return $this->query()
            ->whereHas('enrollments', fn ($query) => $query->where('participant_id', $participantId))
            ->with([
                'category', 'opds', 'employeeStatus',
                'activities',
                'enrollments' => fn ($query) => $query->where('participant_id', $participantId),
                'learns' => fn ($query) => $query->where('participant_id', $participantId),
            ])
            ->get();
    }

    public function findForManagementOverview(int|string $courseId): ?Course
    {
        return $this->query()->with(['category', 'schedules', 'opds', 'employeeStatus'])->find($courseId);
    }

    public function findForManagementDetail(int|string $courseId): ?Course
    {
        return $this->query()->with(['schedules', 'employeeStatus', 'trainers', 'category', 'opds', 'modifier:id,name'])->find($courseId);
    }

    public function isManagedByTrainer(Course $course, int|string $userId): bool
    {
        return $this->query()
            ->whereKey($course->id)
            ->whereHas('trainers', fn ($trainer) => $trainer->where('user_id', $userId))
            ->exists();
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
