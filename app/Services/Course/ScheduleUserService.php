<?php

namespace App\Services\Course;

use App\Repositories\Reporting\Contracts\LearningReportRepositoryInterface;
use Carbon\Carbon;

class ScheduleUserService
{
    public function __construct(private readonly LearningReportRepositoryInterface $reports) {}

    public function getActivationDate($scheduleId, $participantId)
    {
        $query = $this->reports->findScheduleActivationDate($scheduleId, $participantId);

        if (! $query) {
            return null;
        }

        return Carbon::parse($query->created_at)->format('Y-m-d');
    }
}
