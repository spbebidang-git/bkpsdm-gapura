<?php

namespace App\Repositories\Reporting;

use App\Repositories\Reporting\Contracts\LearningReportRepositoryInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;

class LearningReportRepository implements LearningReportRepositoryInterface
{
    public function __construct(private readonly ConnectionInterface $connection) {}

    public function getPostTestResponses(int|string $courseId, int|string $participantId): Collection
    {
        return $this->connection->table('learn_items')
            ->join('learns', 'learn_items.learn_id', '=', 'learns.id')
            ->join('activities', function ($join) {
                $join->on('activities.course_id', '=', 'learns.course_id')
                    ->on('activities.id', '=', 'learn_items.activity_id');
            })
            ->where('learns.course_id', $courseId)
            ->where('learns.participant_id', $participantId)
            ->whereNotNull('learns.finished_at')
            ->select('activities.type as activity_type', 'learn_items.response')
            ->get();
    }

    public function getPerformanceResponses(): Collection
    {
        return $this->connection->table('learns')
            ->join('learn_items', 'learn_items.learn_id', '=', 'learns.id')
            ->join('courses', 'courses.id', '=', 'learns.course_id')
            ->select(
                'learns.id as learn_id',
                'learns.course_id',
                'courses.title as course_title',
                'learn_items.type',
                'learn_items.response',
                'learns.finished_at',
            )
            ->whereIn('learn_items.type', ['pre-test', 'post-test'])
            ->whereNotNull('learns.finished_at')
            ->get();
    }

    public function getParticipantCountsByCourse(): Collection
    {
        return $this->connection->table('schedule_user')
            ->join('schedules', 'schedules.id', '=', 'schedule_user.schedule_id')
            ->selectRaw('schedules.course_id, COUNT(DISTINCT schedule_user.participant_id) as total_peserta')
            ->groupBy('schedules.course_id')
            ->get()
            ->keyBy('course_id');
    }

    public function findScheduleActivationDate(int|string $scheduleId, int|string $participantId): mixed
    {
        return $this->connection->table('schedule_user')
            ->where('schedule_id', $scheduleId)
            ->where('participant_id', $participantId)
            ->first();
    }
}
