<?php

namespace App\Repositories\Learning;

use App\Models\Learning\Learn;
use App\Repositories\EloquentRepository;
use App\Repositories\Learning\Contracts\LearnRepositoryInterface;
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
        $query = $this->query()->with('course');

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
}
