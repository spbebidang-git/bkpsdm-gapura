<?php

namespace App\Repositories\Reporting\Contracts;

use Illuminate\Support\Collection;

interface ResultRepositoryInterface
{
    public function getRawResults(int|string $courseId, array $filters = []): Collection;

    public function courseTable();

    public function participantOptions();

    public function activityOptions(string $courseId);
}
