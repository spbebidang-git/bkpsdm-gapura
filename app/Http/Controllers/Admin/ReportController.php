<?php

namespace App\Http\Controllers\Admin;

use App\Models\Course\Activity\Activity;
use App\Models\Course\Course;
use App\Models\Identity\User;
use App\Services\Reporting\LearningReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class ReportController
{
    public function __construct(private readonly LearningReportService $reports) {}

    public function index(Request $request)
    {
        $filters = $this->filters($request);

        if ($request->ajax() && ! $request->header('X-Inertia')) {
            return $this->data($request);
        }

        return Inertia::render('Report', [
            'filters' => $filters,
            'summary' => $this->reports->summary($filters),
            'courses' => Course::query()
                ->select('id', 'title')
                ->orderBy('title')
                ->get(),
            'participants' => User::role('participant')
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
            'activityTypes' => LearningReportService::ACTIVITY_TYPES,
            'statusOptions' => [
                ['value' => 'not_started', 'label' => 'Belum dimulai'],
                ['value' => 'available', 'label' => 'Tersedia'],
                ['value' => 'in_progress', 'label' => 'Sedang berjalan'],
                ['value' => 'done', 'label' => 'Selesai'],
            ],
        ]);
    }

    public function data(Request $request)
    {
        $filters = $this->filters($request);
        $query = $this->reports->query($filters);
        $service = $this->reports;
        $decorated = [];
        $decorate = function ($row) use ($service, &$decorated) {
            $key = $row->enrollment_id.'-'.$row->activity_id;
            return $decorated[$key] ??= $service->decorate($row);
        };

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('participant_name', fn ($row) => $decorate($row)['participant_name'])
            ->addColumn('nip', fn ($row) => $decorate($row)['nip'])
            ->addColumn('jabatan', fn ($row) => $decorate($row)['jabatan'])
            ->editColumn('course_title', fn ($row) => $decorate($row)['course_title'])
            ->editColumn('schedule_title', fn ($row) => $decorate($row)['schedule_title'])
            ->editColumn('activity_title', fn ($row) => $decorate($row)['activity_title'])
            ->editColumn('activity_type', fn ($row) => $decorate($row)['activity_type'])
            ->editColumn('activity_value', fn ($row) => $decorate($row)['activity_value'])
            ->addColumn('activity_duration', fn ($row) => $decorate($row)['activity_duration'])
            ->addColumn('status', fn ($row) => $decorate($row)['status'])
            ->addColumn('status_label', fn ($row) => $decorate($row)['status_label'])
            ->addColumn('score', fn ($row) => $decorate($row)['score'])
            ->addColumn('correct', fn ($row) => $decorate($row)['correct'])
            ->addColumn('wrong', fn ($row) => $decorate($row)['wrong'])
            ->addColumn('response_available', fn ($row) => $decorate($row)['response_available'])
            ->addColumn('attachment_url', fn ($row) => $decorate($row)['attachment_url'])
            ->editColumn('enrollment_status', fn ($row) => $decorate($row)['enrollment_status'])
            ->editColumn('enrolled_at', fn ($row) => $decorate($row)['enrolled_at'])
            ->editColumn('finished_at', fn ($row) => $decorate($row)['finished_at'])
            ->removeColumn([
                'nip_meta_value',
                'learn_item_id',
                'learn_item_status',
                'learn_item_response',
                'interactive_duration',
                'literature_duration',
                'task_attachment_path',
            ])
            ->make(true);
    }

    public function groups(Request $request)
    {
        $groupBy = $request->string('group_by')->toString();
        abort_unless(in_array($groupBy, ['course', 'participant'], true), 422, 'Group tidak valid.');

        $rows = $this->reports->query($this->filters($request))
            ->get()
            ->map(fn ($row) => $this->reports->decorate($row));

        $groups = $rows->groupBy($groupBy === 'course' ? 'course_id' : 'participant_id')
            ->map(function ($items, $key) use ($groupBy) {
                $scores = $items->pluck('score')->filter(fn ($score) => $score !== null)->values();
                $preScores = $items->where('activity_type', 'pre-test')->pluck('score')->filter(fn ($score) => $score !== null)->values();
                $postScores = $items->where('activity_type', 'post-test')->pluck('score')->filter(fn ($score) => $score !== null)->values();
                $done = $items->where('status', 'done')->count();
                $total = $items->count();
                $first = $items->first();

                return [
                    'group_id' => $key,
                    'course_id' => $first['course_id'],
                    'participant_id' => $first['participant_id'],
                    'label' => $groupBy === 'course' ? $first['course_title'] : $first['participant_name'],
                    'secondary' => $groupBy === 'course'
                        ? $first['schedule_title']
                        : ($first['nip'] !== '-' ? $first['nip'] : $first['jabatan']),
                    'course_count' => $items->pluck('course_id')->unique()->count(),
                    'participant_count' => $items->pluck('participant_id')->unique()->count(),
                    'activity_count' => $total,
                    'done_count' => $done,
                    'completion' => $total ? round(($done / $total) * 100, 1) : 0,
                    'avg_score' => $scores->isNotEmpty() ? round($scores->avg(), 2) : null,
                    'avg_pre_test' => $preScores->isNotEmpty() ? round($preScores->avg(), 2) : null,
                    'avg_post_test' => $postScores->isNotEmpty() ? round($postScores->avg(), 2) : null,
                    'updated_at' => $items->pluck('activity_updated_at')->filter()->max(),
                ];
            })
            ->values();

        return DataTables::of($groups)->addIndexColumn()->make(true);
    }

    private function filters(Request $request): array
    {
        return array_filter([
            'course_id' => $request->string('course_id')->toString(),
            'participant_id' => $request->string('participant_id')->toString(),
            'activity_type' => $request->string('activity_type')->toString(),
            'status' => $request->string('status')->toString(),
            'enrolled_from' => $request->date('enrolled_from')?->toDateString(),
            'enrolled_to' => $request->date('enrolled_to')?->toDateString(),
            'activity_from' => $request->date('activity_from')?->toDateString(),
            'activity_to' => $request->date('activity_to')?->toDateString(),
        ], fn ($value) => filled($value));
    }
}
