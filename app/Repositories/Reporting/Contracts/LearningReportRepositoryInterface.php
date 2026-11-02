<?php

namespace App\Repositories\Reporting\Contracts;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;

interface LearningReportRepositoryInterface
{
    public function query(array $filters = []): Builder;

    public function getPostTestResponses(int|string $courseId, int|string $participantId): Collection;

    public function getPerformanceResponses(): Collection;

    public function getParticipantCountsByCourse(): Collection;

    public function findScheduleActivationDate(int|string $scheduleId, int|string $participantId): mixed;

    public function adminDashboardCounts($user): array;

    public function popularCourses($user);

    public function reportCourses(array $filters);

    public function reportParticipants(array $filters);

    public function detailCourses($rows);

    public function detailParticipants($rows);

    public function trainerCourseIds($user): array;

    public function activityQuery(array $filters);

    public function rows(array $filters);
}
