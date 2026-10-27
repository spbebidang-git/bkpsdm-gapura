<?php

namespace App\Repositories\Reporting\Contracts;

use Illuminate\Support\Collection;
use Illuminate\Database\Query\Builder;

interface LearningReportRepositoryInterface
{
    public function query(array $filters = []): Builder;

    public function getPostTestResponses(int|string $courseId, int|string $participantId): Collection;

    public function getPerformanceResponses(): Collection;

    public function getParticipantCountsByCourse(): Collection;

    public function findScheduleActivationDate(int|string $scheduleId, int|string $participantId): mixed;
}
