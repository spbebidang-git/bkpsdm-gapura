<?php

namespace App\Http\Controllers\Admin;

use App\Models\Course\Category;
use App\Models\Course\Course;
use App\Models\Identity\EmployeeStatus;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\CourseService;
use App\Services\Course\TrainerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class CourseController
{
    public function __construct(protected CourseService $course, protected ActivityService $activity, protected TrainerService $trainer) {}

    public function index()
    {
        if (request()->ajax() && ! request()->header('X-Inertia')) {
            return DataTables::of($this->course->getByAdmin())
                ->addIndexColumn()
                ->make();
        }

        return Inertia::render('Admin/Course/Index');
    }

    public function overview(Course $course)
    {
        $course->load(['category', 'schedules']);

        $finishedParticipant = DB::table('learns')
            ->join('users', 'users.id', '=', 'learns.participant_id')
            ->leftJoin('user_meta as job_title_meta', function ($join) {
                $join->on('job_title_meta.user_id', '=', 'users.id')
                    ->where('job_title_meta.key', '=', 'job_title');
            })
            ->where('learns.course_id', $course->id)
            ->whereNotNull('learns.finished_at')
            ->select('users.name', 'job_title_meta.value as job_title')
            ->get();

        $onGoingParticipant = DB::table('learns')
            ->join('users', 'users.id', '=', 'learns.participant_id')
            ->where('learns.course_id', $course->id)
            ->whereNull('learns.finished_at')
            ->select('users.name')
            ->get();

        return Inertia::render('Admin/Course/Show', [
            'course' => [
                'id' => $course->id,
                'title' => $course->title,
                'slug' => $course->slug,
                'type' => $course->type,
                'status' => $course->status,
                'jam_pelajaran' => $course->jam_pelajaran,
                'short_description' => $course->short_description,
                'thumbnail' => $course->thumbnail_src,
                'category' => $course->category?->title,
                'total_participant' => $course->total_participant,
                'total_passed' => $course->total_passed,
            ],
            'schedules' => $course->schedules->map(fn ($s) => [
                'id' => $s->id,
                'date' => $s->date,
                'time_from' => $s->time_from,
                'time_to' => $s->time_to,
                'quota' => $s->quota,
            ]),
            'finishedParticipant' => $finishedParticipant,
            'onGoingParticipant' => $onGoingParticipant,
        ]);
    }

    public function create()
    {
        return Inertia::render('Instructor/Course/Create', [
            'employee_status' => EmployeeStatus::all(),
            'categories' => Category::all(),
            'trainers' => $this->trainer->getForForm(),
        ]);
    }

    public function store(Request $request)
    {
        if (is_string($request->schedules)) {
            $request->merge([
                'schedules' => json_decode($request->schedules, true)  // Dekode menjadi array
            ]);
        }

        $request->validate([
            'type' => 'required|string',
            'category' => 'required|numeric',
            'employee_status'   => 'required|min:1',
            'employee_status.*' => 'exists:employee_status,id',
            'title' => 'required|string',
            'short_description' => 'required|string',
            'description' => 'required|string',
            'thumbnail' => 'required|image|max:10240|mimes:png,jpg',
            'teaser_video' => 'nullable|string',
            'expired' => 'required_if:type,unschedule',
            'status' => 'required|string',
            'jam_pelajaran' => 'required|string',
            'schedules' => 'required_if:type,schedule|array|min:1',
            'trainers' => 'nullable|array',
            'trainers.*' => 'string|exists:trainers,id',
        ]);

        try {
            DB::beginTransaction();

            $data = $request->only('type', 'category', 'employee_status', 'title', 'short_description', 'description', 'thumbnail', 'teaser_video', 'expired', 'status', 'jam_pelajaran', 'trainers');
            $data['description'] = sanitize_html($data['description']);
            $data['short_description'] = sanitize_plain_text($data['short_description']);

            $this->course->createCourse(
                $data,
                $request->schedules
            );

            DB::commit();

            return $this->afterCourseMutation('Course saved successfully.');
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Course store failed: ' . $th->getMessage(), [
                'trace' => $th->getTraceAsString(),
            ]);
            return redirect()->back()->withInput()->with('danger', 'Something went wrong: ' . $th->getMessage());
        }
    }

    public function show(Course $course)
    {
        return Inertia::render('Instructor/Course/Show', [
            'course' => [
                'title' => $course->title,
                'slug' => $course->slug,
                'value' => $course->value,
                'short_description' => $course->short_description,
                'description' => $course->description,
                'thumbnail' => $course->thumbnail_src,
                'total_participant' => $course->total_participant,
                'total_passed' => $course->total_passed,
                'efektifitas' => $course->efektifitas,
                'is_locked' => (bool)$course->is_locked,
            ],
            'activities' => $this->activity->getActivityByCourse($course->id),
            'trainers' => $course->load('trainers')->trainers->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'jabatan' => $t->jabatan,
                'photo_src' => $t->photo_src,
            ]),
        ]);
    }

    public function edit(Course $course)
    {
        // ambil id employee_status yg sudah terpilih
        $selectedEmployeeStatus = $course->employeeStatus()->pluck('employee_status_id')->toArray();

        return Inertia::render('Instructor/Course/Edit', [
            'course' => $course,
            'employee_status' => Course::EMPLOYEE_STATUS,
            'schedules' => $course->load('schedules')->schedules->map(function ($row) {
                $timeFrom = $row->getRawOriginal('time_from');
                $timeTo = $row->getRawOriginal('time_to');

                return [
                    'id' => $row->id,
                    'date' => $row->getRawOriginal('date'),
                    'time_from' => $timeFrom ? \Carbon\Carbon::parse($timeFrom)->format('H:i') : null,
                    'time_to' => $timeTo ? \Carbon\Carbon::parse($timeTo)->format('H:i') : null,
                    'quota' => $row->getRawOriginal('quota'),
                ];
            }),
            'categories' => Category::all(),
            'employee_status' => EmployeeStatus::all(),
            'selected_employee_status' => $selectedEmployeeStatus,
            'trainers' => $this->trainer->getForForm(),
            'selected_trainers' => $course->load('trainers')->trainers->pluck('id')->toArray(),
        ]);
    }

    public function update(Course $course, Request $request)
    {
        // dd($request->all());
        if (is_string($request->schedules)) {
            $request->merge([
                'schedules' => json_decode($request->schedules, true)  // Dekode menjadi array
            ]);
        }

        $request->validate([
            'type' => 'required|string',
            'category' => 'required|numeric',
            'employee_status'   => 'required|min:1',
            'employee_status.*' => 'exists:employee_status,id',
            'title' => 'required|string',
            'short_description' => 'required|string',
            'description' => 'required|string',
            'thumbnail' => 'nullable|image|max:10240|mimes:png,jpg',
            'teaser_video' => 'nullable|string',
            'expired' => 'required_if:type,unschedule',
            'status' => 'required|string',
            'jam_pelajaran' => 'required|string',
            'schedules' => 'required_if:type,schedule|array|min:1',
            'trainers' => 'nullable|array',
            'trainers.*' => 'string|exists:trainers,id',
        ]);

        try {
            DB::beginTransaction();

            $data = $request->only('type', 'category', 'employee_status', 'title', 'short_description', 'description', 'thumbnail', 'teaser_video', 'expired', 'status', 'jam_pelajaran', 'trainers');
            $data['description'] = sanitize_html($data['description']);
            $data['short_description'] = sanitize_plain_text($data['short_description']);

            $this->course->updateCourse(
                $course,
                $data,
                $request->schedules
            );

            DB::commit();

            return $this->afterCourseMutation('Course updated successfully.');
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('Course update failed: ' . $th->getMessage(), [
                'trace' => $th->getTraceAsString(),
            ]);
            return redirect()->back()->withInput()->with('danger', 'Something went wrong: ' . $th->getMessage());
        }
    }

    public function destroy(Course $course)
    {
        $course->delete();

        return $this->afterCourseMutation('Course deleted successfully.');
    }

    public function lock(Course $course)
    {
        if ($course->is_locked) {
            return redirect()->back()->with('danger', "Materi sudah dikunci.");
        }

        $course->update([
            'is_locked' => true
        ]);

        return redirect()->back()->with('success', "Materi berhasil dikunci.");
    }

    protected function afterCourseMutation(string $message)
    {
        return to_route('admin.course.index')->with('success', $message);
    }
}
