<?php

namespace App\Repositories\Learning;

use App\Models\Learning\Learn;
use App\Repositories\EloquentRepository;
use App\Repositories\Learning\Contracts\LearnRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class LearnRepository extends EloquentRepository implements LearnRepositoryInterface
{
    public function __construct(Learn $model)
    {
        parent::__construct($model);
    }

    public function findByParticipant(int|string $participantId, array $relations = []): ?Learn
    {
        return $this->query()->with($relations)->where('participant_id', $participantId)->first();
    }

    public function findByParticipantAndCourse(
        int|string $participantId,
        int|string $courseId,
        array $relations = [],
    ): ?Learn {
        return $this->query()
            ->with($relations)
            ->where('participant_id', $participantId)
            ->where('course_id', $courseId)
            ->first();
    }

    public function findByEnrollment(int|string $enrollmentId): ?Learn
    {
        return $this->query()->with('learnItems')->where('enrollment_id', $enrollmentId)->first();
    }

    public function lockByEnrollment(int|string $enrollmentId): ?Learn
    {
        return $this->query()->where('enrollment_id', $enrollmentId)->lockForUpdate()->first();
    }

    public function deleteWithItems(Learn $learn): void
    {
        $learn->learnItems()->delete();
        $learn->delete();
    }

    public function getByCourse(int|string $courseId): Collection
    {
        return $this->query()->with(['participant', 'learnItems'])->where('course_id', $courseId)->get();
    }

    public function getCompletedByCourse(int|string $courseId): Collection
    {
        return $this->query()
            ->where('course_id', $courseId)
            ->whereNotNull('finished_at')
            ->with(['participant.metas' => fn ($query) => $query->where('key', 'job_title')])
            ->get();
    }

    public function getOngoingByCourse(int|string $courseId): Collection
    {
        return $this->query()
            ->where('course_id', $courseId)
            ->whereNull('finished_at')
            ->with('participant')
            ->get();
    }

    public function getForCertificateSync(
        int|string|null $courseId = null,
        int|string|null $learnId = null,
        bool $onlyHasUrl = false,
    ): Collection {
        $query = $this->query()->with(['course', 'participant.metas']);

        if ($learnId !== null) {
            $query->whereKey($learnId);
        } elseif ($courseId !== null) {
            $query->where('course_id', $courseId);
        }

        if ($onlyHasUrl) {
            $query->whereNotNull('url_certificate')
                ->where('url_certificate', '!=', '')
                ->where('url_certificate', '!=', '-');
        }

        return $query->get();
    }

    public function lockForParticipantCourse(string $participantId, string $courseId): Learn
    {
        return $this->query()->where('course_id', $courseId)->where('participant_id', $participantId)->lockForUpdate()->firstOrFail();
    }

    public function certificateQuery(array $filters): Builder
    {

        $query = Learn::query()
            ->with(['course', 'participant'])
            ->whereNotNull('finished_at')
            ->latest('finished_at')
            ->latest('id')
            ->select('learns.*');

        $statusFilter = ($filters['status_filter'] ?? null);
        if ($statusFilter === 'menunggu') {
            $query->where(function ($q) {
                $q->where('is_post_certificate', false)
                    ->orWhereNull('is_post_certificate');
            })->where(function ($q) {
                $q->whereNull('url_certificate')
                    ->orWhere('url_certificate', '')
                    ->orWhere('url_certificate', '-');
            });
        } elseif ($statusFilter === 'dikirim') {
            $query->where('is_post_certificate', true)
                ->where(function ($q) {
                    $q->whereNull('url_certificate')
                        ->orWhere('url_certificate', '')
                        ->orWhere('url_certificate', '-');
                });
        } elseif ($statusFilter === 'tidak_tersedia') {
            $query->where(fn ($q) => $q->whereNull('url_certificate')->orWhere('url_certificate', '')->orWhere('url_certificate', '-'));
        } elseif ($statusFilter === 'tersedia') {
            $query->whereNotNull('url_certificate')
                ->where('url_certificate', '!=', '')
                ->where('url_certificate', '!=', '-');
        }

        if ($courseFilter = ($filters['course_filter'] ?? null)) {
            $query->where('course_id', $courseFilter);
        }
        if ($dateFrom = ($filters['date_from'] ?? null)) {
            $query->whereDate('finished_at', '>=', $dateFrom);
        }
        if ($dateTo = ($filters['date_to'] ?? null)) {
            $query->whereDate('finished_at', '<=', $dateTo);
        }

        if ($search = trim((string) ($filters['q'] ?? ''))) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('participant', fn ($p) => $p->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('course', fn ($c) => $c->where('title', 'like', "%{$search}%"));
            });
        }

        return $query;

    }

    public function filterCertificateColumn($query, string $relation, string $field, string $keyword): void
    {
        $query->whereHas($relation, fn ($q) => $q->where($field, 'like', "%{$keyword}%"));
    }

    public function lockById(string $id): ?Learn
    {
        return $this->query()->lockForUpdate()->find($id);
    }

    public function completeActiveEnrollment(Learn $learn): void
    {
        $learn->enrollment()->where('status', 'active')->update(['status' => 'completed']);
    }
}
