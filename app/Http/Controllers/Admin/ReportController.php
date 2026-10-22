<?php

namespace App\Http\Controllers\Admin;

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
        $filters = $this->filters($request, false);

        if ($request->ajax() && ! $request->header('X-Inertia')) {
            return $this->data($request);
        }

        return Inertia::render('Report', [
            'filters' => array_diff_key($filters, ['course_ids' => true]),
            'view' => $request->query('view') === 'course' ? 'course' : 'participant',
            'summary' => $this->reports->summary($filters),
            'courses' => Course::query()
                ->when(isset($filters['course_ids']), fn ($query) => $query->whereIn('id', $filters['course_ids']))
                ->select('id', 'title')
                ->orderBy('title')
                ->get(),
            'participants' => User::role('participant')
                ->when(isset($filters['course_ids']), fn ($query) => $query->whereHas('enrollments', fn ($enrollments) => $enrollments->whereIn('course_id', $filters['course_ids'])))
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function data(Request $request)
    {
        $filters = $this->filters($request);
        $query = $this->reports->query($filters)->whereNotNull('activities.id');
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

        $rows = $this->reports->query($this->filters($request, false))
            ->get()
            ->map(fn ($row) => $this->reports->decorate($row));

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

        return DataTables::of($groups)->addIndexColumn()
            ->filter(function ($table) use ($request) {
                $search = mb_strtolower(trim((string) $request->input('search.value', '')));
                if ($search !== '') {
                    $table->collection = $table->collection->filter(fn ($row) => str_contains(mb_strtolower($row['label'].' '.$row['secondary']), $search))->values();
                }
            })->make(true);
    }

    public function participant(Request $request, User $participant)
    {
        abort_unless($participant->hasRole('participant'), 404);
        return $this->detail($request, 'participant', $participant->id, $participant->name);
    }

    public function course(Request $request, Course $course)
    {
        return $this->detail($request, 'course', $course->id, $course->title);
    }

    private function detail(Request $request, string $type, string $id, string $title)
    {
        $filters = $this->filters($request);
        $fixedKey = $type === 'course' ? 'course_id' : 'participant_id';
        $filters[$fixedKey] = $id;
        $scope = [$fixedKey => $id];
        if (isset($filters['course_ids'])) $scope['course_ids'] = $filters['course_ids'];
        $rows = $this->reports->query($scope)->get();
        if ($request->user()->hasRole('trainer') && ! $request->user()->hasAnyRole(['super_admin', 'administrator'])) {
            abort_unless($rows->isNotEmpty(), 404);
        }
        $reportUrl = route('admin.report');
        $backUrl = $request->string('return_to')->toString();
        if ($backUrl !== $reportUrl && ! str_starts_with($backUrl, $reportUrl.'?')) {
            $backUrl = $reportUrl.'?view='.$type;
        }

        return Inertia::render('Admin/Report/Show', [
            'backUrl' => $backUrl,
            'entity' => ['id' => $id, 'title' => $title, 'type' => $type],
            'filters' => array_diff_key($filters, ['course_ids' => true]),
            'summary' => $this->reports->summary($filters),
            'courses' => Course::query()
                ->whereIn('id', $rows->pluck('course_id')->unique())
                ->orderBy('title')->get(['id', 'title']),
            'participants' => User::query()->whereIn('id', $rows->pluck('participant_id')->unique())
                ->orderBy('name')->get(['id', 'name']),
            'activityTypes' => LearningReportService::ACTIVITY_TYPES,
        ]);
    }

    private function filters(Request $request, bool $details = true): array
    {
        $filters = array_filter([
            'course_id' => $request->string('course_id')->toString(),
            'participant_id' => $request->string('participant_id')->toString(),
            'activity_type' => $request->string('activity_type')->toString(),
            'status' => $request->string('status')->toString(),
            'enrolled_from' => $request->date('enrolled_from')?->toDateString(),
            'enrolled_to' => $request->date('enrolled_to')?->toDateString(),
            'activity_from' => $request->date('activity_from')?->toDateString(),
            'activity_to' => $request->date('activity_to')?->toDateString(),
        ], fn ($value) => filled($value));
        if (! $details) {
            $filters = array_intersect_key($filters, array_flip(['course_id', 'participant_id', 'enrolled_from', 'enrolled_to']));
        }
        $user = $request->user();
        if ($user?->hasRole('trainer') && ! $user->hasAnyRole(['super_admin', 'administrator'])) {
            $filters['course_ids'] = Course::query()->whereHas('trainers', fn ($query) => $query->where('user_id', $user->id))->pluck('id')->all();
        }
        return $filters;
    }
}
