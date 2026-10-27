<?php

namespace App\Repositories\Reporting;

use App\Repositories\Reporting\Contracts\LearningReportRepositoryInterface;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Illuminate\Database\Query\Builder;

class LearningReportRepository implements LearningReportRepositoryInterface
{
    public function __construct(private readonly ConnectionInterface $connection) {}

    public function query(array $filters = []): Builder
    {
        $query = $this->connection->table('enrollments')
            ->join('users', 'users.id', '=', 'enrollments.participant_id')
            ->join('courses', 'courses.id', '=', 'enrollments.course_id')
            ->leftJoin('activities', function ($join) {
                $join->on('activities.course_id', '=', 'enrollments.course_id')->whereNull('activities.deleted_at');
            })
            ->leftJoin('schedules', 'schedules.id', '=', 'enrollments.schedule_id')
            ->leftJoin('learns', function ($join) {
                $join->on('learns.enrollment_id', '=', 'enrollments.id')
                    ->whereNull('learns.deleted_at');
            })
            ->leftJoin('learn_items', function ($join) {
                $join->on('learn_items.learn_id', '=', 'learns.id')
                    ->on('learn_items.activity_id', '=', 'activities.id')
                    ->whereNull('learn_items.deleted_at');
            })
            ->leftJoin('user_meta as nip_meta', function ($join) {
                $join->on('nip_meta.user_id', '=', 'users.id')
                    ->where('nip_meta.key', '=', 'nip');
            })
            ->leftJoin('user_meta as jabatan_meta', function ($join) {
                $join->on('jabatan_meta.user_id', '=', 'users.id')
                    ->where('jabatan_meta.key', '=', 'nama_jabatan');
            })
            ->leftJoin('interactives', function ($join) {
                $join->on('interactives.activity_id', '=', 'activities.id')
                    ->on('interactives.type', '=', 'activities.type')
                    ->whereNull('interactives.deleted_at');
            })
            ->leftJoin('literatures', function ($join) {
                $join->on('literatures.activity_id', '=', 'activities.id')
                    ->whereNull('literatures.deleted_at');
            })
            ->leftJoin('tasks', function ($join) {
                $join->on('tasks.activity_id', '=', 'activities.id')
                    ->whereNull('tasks.deleted_at');
            })
            ->whereNull('enrollments.deleted_at')
            ->whereNull('users.deleted_at')
            ->whereNull('courses.deleted_at')
            ->whereNull('activities.deleted_at')
            ->select([
                'enrollments.id as enrollment_id',
                'enrollments.status as enrollment_status',
                'enrollments.enrolled_at',
                'enrollments.schedule_id',
                'schedules.title as schedule_title',
                'users.id as participant_id',
                'users.name as participant_name',
                'users.nip as participant_nip',
                'nip_meta.value as nip_meta_value',
                'jabatan_meta.value as jabatan',
                'courses.id as course_id',
                'courses.title as course_title',
                'activities.id as activity_id',
                'activities.title as activity_title',
                'activities.type as activity_type',
                'activities.value as activity_value',
                'activities.timer as activity_timer',
                'learns.finished_at',
                'learn_items.id as learn_item_id',
                'learn_items.status as learn_item_status',
                'learn_items.response as learn_item_response',
                'learn_items.updated_at as updated_at',
                'interactives.duration as interactive_duration',
                'literatures.duration as literature_duration',
                'tasks.attachment_path as task_attachment_path',
            ]);

        if (! empty($filters['course_id'])) {
            $query->where('courses.id', $filters['course_id']);
        }

        if (array_key_exists('course_ids', $filters)) {
            $query->whereIn('courses.id', $filters['course_ids']);
        }

        if (! empty($filters['participant_id'])) {
            $query->where('users.id', $filters['participant_id']);
        }

        if (! empty($filters['activity_type']) && in_array($filters['activity_type'], ['pre-test', 'post-test', 'task', 'video', 'meeting', 'literature'], true)) {
            $query->where('activities.type', $filters['activity_type']);
        }

        if (! empty($filters['status'])) {
            if ($filters['status'] === 'not_started') {
                $query->whereNull('learn_items.id');
            } elseif (in_array($filters['status'], ['available', 'in_progress', 'done'], true)) {
                $query->where('learn_items.status', $filters['status']);
            }
        }

        if (! empty($filters['enrolled_from'])) {
            $query->whereDate('enrollments.enrolled_at', '>=', $filters['enrolled_from']);
        }

        if (! empty($filters['enrolled_to'])) {
            $query->whereDate('enrollments.enrolled_at', '<=', $filters['enrolled_to']);
        }

        if (! empty($filters['activity_from'])) {
            $query->whereDate('learn_items.updated_at', '>=', $filters['activity_from']);
        }

        if (! empty($filters['activity_to'])) {
            $query->whereDate('learn_items.updated_at', '<=', $filters['activity_to']);
        }

        return $query;
    }

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
            ->whereNull('learns.deleted_at')
            ->whereNull('learn_items.deleted_at')
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
            ->whereNull('learns.deleted_at')
            ->whereNull('learn_items.deleted_at')
            ->whereNotNull('learns.finished_at')
            ->get();
    }

    public function getParticipantCountsByCourse(): Collection
    {
        return $this->connection->table('enrollments')
            ->whereNotNull('schedule_id')
            ->whereNull('deleted_at')
            ->selectRaw('course_id, COUNT(DISTINCT participant_id) as total_peserta')
            ->groupBy('course_id')
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
