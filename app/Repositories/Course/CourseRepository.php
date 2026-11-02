<?php

namespace App\Repositories\Course;

use App\Models\Course\Course;
use App\Models\Course\Trainer;
use App\Models\Identity\User;
use App\Repositories\Course\Contracts\CourseRepositoryInterface;
use App\Repositories\EloquentRepository;
use App\Services\Course\CourseAudience;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;

class CourseRepository extends EloquentRepository implements CourseRepositoryInterface
{
    public function __construct(Course $model)
    {
        parent::__construct($model);
    }

    public function lock(int|string $id): Course
    {
        return $this->query()->whereKey($id)->lockForUpdate()->firstOrFail();
    }

    public function scheduleCount(int|string $courseId): int
    {
        return $this->query()->findOrFail($courseId)->schedules()->count();
    }

    public function getPublishedOptions(): Collection
    {
        return $this->query()->where('status', 'publish')->orderBy('title')->get(['id', 'title']);
    }

    public function findMany(array $ids): Collection
    {
        return $this->query()->whereIn('id', $ids)->get();
    }

    public function isVisible(Course $course, ?User $user): bool
    {
        return $this->query()->whereKey($course->id)
            ->where(fn ($query) => app(CourseAudience::class)->viewable($query, $user))
            ->exists();
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
            ->where(fn ($q) => app(CourseAudience::class)->viewable($q, $user))->first();
    }

    public function getPublishedAvailable(?DateTimeInterface $at = null, ?int $limit = null, array $filters = [], ?User $user = null): Collection
    {
        $now = $at ? Carbon::instance($at) : now();
        $date = $now->toDateString();
        $time = $now->format('H:i:s');

        $query = $this->query()
            ->select('courses.*')
            ->with(['category', 'employeeStatus', 'opds'])
            ->withCount([
                'learns as total_passed_count' => fn ($query) => $query->whereNotNull('finished_at'),
                'reviews as rating_count',
            ])
            ->withSum('reviews as rating_total', 'star')
            ->selectSub(function ($query) {
                $query->from('enrollments')
                    ->whereColumn('enrollments.course_id', 'courses.id')
                    ->whereNull('enrollments.deleted_at')
                    ->selectRaw('count(distinct enrollments.participant_id)');
            }, 'total_participant_count')
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
            ->when($filters['visibility'] ?? null, fn ($query, $visibility) => $query->where('visibility', $visibility))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('title', 'like', "%{$search}%"))
            ->when($filters['category'] ?? null, fn ($query, $categoryId) => $query->where('category_id', $categoryId))
            ->when($filters['employee_status'] ?? null, fn ($query, $employeeStatus) => $query->whereHas(
                'employeeStatus',
                fn ($statusQuery) => $statusQuery->where('title', $employeeStatus)
            ));

        // Batasi katalog sesuai audiens: tamu hanya kelas publik, peserta juga
        // mendapat kelas privat yang menyasar OPD dan status kepegawaiannya.
        app(CourseAudience::class)->visible($query, $user);

        $query->orderBy('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    public function getFeaturedPublishedCourses(DateTimeInterface $at, int $limit = 6, ?User $user = null): Collection
    {
        $now = Carbon::instance($at);

        return $this->query()
            ->select('courses.*')
            ->with(['category', 'employeeStatus', 'opds'])
            ->withCount([
                'learns as total_passed_count' => fn ($query) => $query->whereNotNull('finished_at'),
                'reviews as rating_count',
            ])
            ->withSum('reviews as rating_total', 'star')
            ->selectSub(function ($query) {
                $query->from('enrollments')
                    ->whereColumn('enrollments.course_id', 'courses.id')
                    ->whereNull('enrollments.deleted_at')
                    ->selectRaw('count(distinct enrollments.participant_id)');
            }, 'total_participant_count')
            ->where('status', 'publish')
            ->where('visibility', 'public')
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
            if (! Trainer::activeOpdIdsForUser($instructorId)) {
                $query->whereRaw('1 = 0');
            }
        }

        return $query->orderByDesc('courses.updated_at')->orderByDesc('courses.id')->get();
    }

    public function findBrowseableBySlug(string $slug, array $relations = []): ?Course
    {
        return $this->query()->with($relations)->where('slug', $slug)
            ->where('status', 'publish')->first();
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

    public function certificateCourses()
    {
        return $this->query()->whereHas('learns', fn ($q) => $q->whereNotNull('finished_at'))->orderBy('title')->get(['id', 'title']);
    }

    public function importCourses()
    {
        return $this->query()->where('access_mode', 'scheduled')->where('status', 'publish')->orderBy('title')->get(['id', 'title']);
    }

    public function assignedTrainerIds(Course $course): array
    {
        return $course->trainers()->pluck('trainers.id')->all();
    }

    public function syncAudienceOpds(Course $course, array $ids): void
    {
        $course->opds()->sync($ids);
    }

    public function hasTrainer(Course $course, string $userId): bool
    {
        return $course->trainers()->where('user_id', $userId)->exists();
    }
}
