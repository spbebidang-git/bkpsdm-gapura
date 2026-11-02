<?php

namespace App\Services\Reporting;

use App\Repositories\Reporting\Contracts\LearningReportRepositoryInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Crypt;

class LearningReportService
{
    public const ACTIVITY_TYPES = [
        'pre-test',
        'post-test',
        'task',
        'video',
        'meeting',
        'literature',
    ];

    public function __construct(private readonly ?LearningReportRepositoryInterface $reports = null) {}

    public function query(array $filters = []): Builder
    {
        return ($this->reports ?? app(LearningReportRepositoryInterface::class))->query($filters);
    }

    public function summary(array $filters = []): array
    {
        $rows = $this->rows($filters);
        $participants = $rows->groupBy('enrollment_id');
        $total = $participants->count();
        $notStarted = 0;
        $inProgress = 0;
        $completed = 0;
        $preScores = [];
        $postScores = [];

        foreach ($participants as $items) {
            $hasActivity = $items->contains(fn ($row) => $row->learn_item_id !== null);
            $classCompleted = $items->contains(fn ($row) => $row->enrollment_status === 'completed' || $row->finished_at !== null);

            if ($classCompleted) {
                $completed++;
            } elseif (! $hasActivity) {
                $notStarted++;
            } else {
                $inProgress++;
            }

            foreach ($items as $row) {
                $metrics = $this->responseMetrics($row->learn_item_response, $row->activity_type);
                if ($row->activity_type === 'pre-test' && $metrics['score'] !== null) {
                    $preScores[] = $metrics['score'];
                }
                if ($row->activity_type === 'post-test' && $metrics['score'] !== null) {
                    $postScores[] = $metrics['score'];
                }
            }
        }

        return [
            'total_participants' => $rows->pluck('participant_id')->unique()->count(),
            'total_courses' => $rows->pluck('course_id')->unique()->count(),
            'total_enrollments' => $total,
            'not_started' => $notStarted,
            'in_progress' => $inProgress,
            'completed' => $completed,
            'avg_pre_test' => $this->average($preScores),
            'avg_post_test' => $this->average($postScores),
        ];
    }

    public function decorate(object $row): array
    {
        $metrics = $this->responseMetrics($row->learn_item_response, $row->activity_type);
        $status = $row->learn_item_id === null ? 'not_started' : ($row->learn_item_status ?: 'available');
        $nip = $row->participant_nip ?: $row->nip_meta_value;

        if ($nip && $nip !== '-') {
            try {
                $nip = Crypt::decryptString($nip);
            } catch (\Throwable) {
                // Keep legacy/plain NIP values as-is.
            }
        }

        $attachment = $this->attachmentName($row->learn_item_response, $row->activity_type);
        $duration = $row->interactive_duration ?? $row->literature_duration;

        return [
            'enrollment_id' => $row->enrollment_id,
            'participant_id' => $row->participant_id,
            'participant_name' => $row->participant_name,
            'nip' => $nip ?: '-',
            'jabatan' => $row->jabatan ?: '-',
            'course_id' => $row->course_id,
            'course_title' => $row->course_title,
            'schedule_title' => $row->schedule_title ?: 'Tanpa jadwal',
            'enrollment_status' => $row->enrollment_status,
            'enrolled_at' => $row->enrolled_at,
            'finished_at' => $row->finished_at,
            'activity_id' => $row->activity_id,
            'activity_title' => $row->activity_title,
            'activity_type' => $row->activity_type,
            'activity_value' => $row->activity_value,
            'activity_timer' => $row->activity_timer,
            'activity_duration' => $duration,
            'status' => $status,
            'status_label' => $this->statusLabel($status),
            'activity_updated_at' => $row->updated_at,
            'score' => $metrics['score'],
            'correct' => $metrics['correct'],
            'wrong' => $metrics['wrong'],
            'response_available' => $metrics['available'],
            'attachment_url' => $attachment ? asset('assets/media/tasks/'.$attachment) : null,
        ];
    }

    public function responseMetrics(?string $response, ?string $type): array
    {
        if (! $response) {
            return ['score' => null, 'correct' => null, 'wrong' => null, 'available' => false];
        }

        $decoded = @unserialize($response, ['allowed_classes' => false]);
        if (! is_array($decoded)) {
            return ['score' => null, 'correct' => null, 'wrong' => null, 'available' => false];
        }

        if (! in_array($type, ['pre-test', 'post-test'], true)) {
            return ['score' => null, 'correct' => null, 'wrong' => null, 'available' => true];
        }

        $score = 0;
        $correct = 0;
        $wrong = 0;
        $hasScore = false;

        foreach ($decoded as $item) {
            if (! is_array($item)) {
                continue;
            }
            if (isset($item['score']) && is_numeric($item['score'])) {
                $score += (float) $item['score'];
                $hasScore = true;
            }
            if (($item['is_correct'] ?? null) === true) {
                $correct++;
            } elseif (($item['is_correct'] ?? null) === false) {
                $wrong++;
            }
        }

        return [
            'score' => $hasScore ? round($score, 2) : null,
            'correct' => $correct,
            'wrong' => $wrong,
            'available' => true,
        ];
    }

    private function attachmentName(?string $response, ?string $type): ?string
    {
        if ($type !== 'task' || ! $response) {
            return null;
        }

        $decoded = @unserialize($response, ['allowed_classes' => false]);

        return is_string($decoded) && $decoded !== '' ? basename($decoded) : null;
    }

    private function statusLabel(string $status): string
    {
        return [
            'not_started' => 'Belum dimulai',
            'available' => 'Tersedia',
            'in_progress' => 'Sedang berjalan',
            'done' => 'Selesai',
        ][$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    private function average(array $scores): ?float
    {
        return count($scores) ? round(array_sum($scores) / count($scores), 2) : null;
    }

    public function reportCourses(array $filters)
    {
        return ($this->reports ?? app(LearningReportRepositoryInterface::class))->reportCourses($filters);
    }

    public function reportParticipants(array $filters)
    {
        return ($this->reports ?? app(LearningReportRepositoryInterface::class))->reportParticipants($filters);
    }

    public function detailCourses($rows)
    {
        return ($this->reports ?? app(LearningReportRepositoryInterface::class))->detailCourses($rows);
    }

    public function detailParticipants($rows)
    {
        return ($this->reports ?? app(LearningReportRepositoryInterface::class))->detailParticipants($rows);
    }

    public function groups(array $filters, string $groupBy)
    {
        $rows = $this->rows($filters)
            ->map(fn ($row) => $this->decorate($row));

        $groups = $rows->groupBy($groupBy === 'course' ? 'course_id' : 'participant_id')
            ->map(function ($items, $key) use ($groupBy) {
                $scores = $items->pluck('score')->filter(fn ($score) => $score !== null)->values();
                $preScores = $items->where('activity_type', 'pre-test')->pluck('score')->filter(fn ($score) => $score !== null)->values();
                $postScores = $items->where('activity_type', 'post-test')->pluck('score')->filter(fn ($score) => $score !== null)->values();
                $done = $items->where('status', 'done')->whereNotNull('activity_id')->count();
                $total = $items->whereNotNull('activity_id')->count();
                $first = $items->first();
                $enrollments = $items->unique('enrollment_id');

                return [
                    'group_id' => $key,
                    'course_id' => $first['course_id'],
                    'participant_id' => $first['participant_id'],
                    'label' => $groupBy === 'course' ? $first['course_title'] : $first['participant_name'],
                    'secondary' => $groupBy === 'course'
                        ? $items->pluck('schedule_title')->unique()->join(', ')
                        : ($first['nip'] !== '-' ? $first['nip'] : $first['jabatan']),
                    'course_count' => $items->pluck('course_id')->unique()->count(),
                    'participant_count' => $items->pluck('participant_id')->unique()->count(),
                    'activity_count' => $total,
                    'done_count' => $done,
                    'completed_count' => $enrollments->filter(fn ($item) => $item['finished_at'] !== null || $item['enrollment_status'] === 'completed')->count(),
                    'completion' => $total ? round(($done / $total) * 100, 1) : 0,
                    'avg_score' => $scores->isNotEmpty() ? round($scores->avg(), 2) : null,
                    'avg_pre_test' => $preScores->isNotEmpty() ? round($preScores->avg(), 2) : null,
                    'avg_post_test' => $postScores->isNotEmpty() ? round($postScores->avg(), 2) : null,
                    'updated_at' => $items->pluck('activity_updated_at')->filter()->max(),
                ];
            })
            ->values();

        return $groups;
    }

    public function trainerCourseIds($user): array
    {
        return ($this->reports ?? app(LearningReportRepositoryInterface::class))->trainerCourseIds($user);
    }

    public function activityQuery(array $filters)
    {
        return ($this->reports ?? app(LearningReportRepositoryInterface::class))->activityQuery($filters);
    }

    public function rows(array $filters)
    {
        return ($this->reports ?? app(LearningReportRepositoryInterface::class))->rows($filters);
    }
}
