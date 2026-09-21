<?php

namespace App\Repositories\Learning\Contracts;

use App\Models\Learning\Learn;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface LearnRepositoryInterface extends BaseRepositoryInterface
{
    public function findByParticipant(int|string $participantId, array $relations = []): ?Learn;

    public function findByParticipantAndCourse(
        int|string $participantId,
        int|string $courseId,
        array $relations = [],
    ): ?Learn;

    public function findByEnrollment(int|string $enrollmentId): ?Learn;

    public function getByCourse(int|string $courseId): Collection;

    public function getForCertificateSync(
        int|string|null $courseId = null,
        int|string|null $learnId = null,
        bool $onlyHasUrl = false,
    ): Collection;
}
