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
            'courses' => $this->reports->reportCourses($filters),
            'participants' => $this->reports->reportParticipants($filters),
        ]);
    }

    public function data(Request $request)
    {
        $filters = $this->filters($request);
        $query = $this->reports->activityQuery($filters);
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

        $groups = $this->reports->groups($this->filters($request, false), $groupBy);

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
        if (isset($filters['course_ids'])) {
            $scope['course_ids'] = $filters['course_ids'];
        }
        $rows = $this->reports->rows($scope);
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
            'courses' => $this->reports->detailCourses($rows),
            'participants' => $this->reports->detailParticipants($rows),
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
            $filters['course_ids'] = $this->reports->trainerCourseIds($user);
        }

        return $filters;
    }
}
