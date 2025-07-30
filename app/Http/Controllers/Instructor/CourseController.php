<?php

namespace App\Http\Controllers\Instructor;

use App\Models\Course\Category;
use App\Models\Course\Course;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\CourseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CourseController
{
    protected $activity, $course, $instructorId;

    public function __construct(ActivityService $activityService, CourseService $courseService)
    {
        $this->activity = $activityService;
        $this->course = $courseService;
        $this->instructorId =  Auth::user()->id;
    }

    public function create()
    {
        return Inertia::render('Instructor/Course/Create', [
            'employee_status' => Course::EMPLOYEE_STATUS,
            'categories' => Category::all()
        ]);
    }

    public function store(Request $request)
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
            'employee_status' => 'required|in:PNS,CPNS,PPPK,THL',
            'title' => 'required|string',
            'short_description' => 'required|string',
            'description' => 'required|string',
            'thumbnail' => 'required|image|max:10240|mimes:png,jpg',
            'teaser_video' => 'nullable|string',
            'expired' => 'required_if:type,unschedule',
            'status' => 'required|string',
            'jam_pelajaran' => 'required|string',
            'schedules' => 'required_if:type,schedule|array|min:1'
        ]);

        try {
            DB::beginTransaction();

            $this->course->createCourse(
                $this->instructorId,
                $request->only('type', 'category', 'employee_status', 'title', 'short_description', 'description', 'thumbnail', 'teaser_video', 'expired', 'status', 'jam_pelajaran'),
                $request->schedules
            );

            DB::commit();

            return to_route('instructor.dashboard')->with('success', 'Course saved successfully.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('instructor.dashboard')->with('danger', 'Something went wrong.');
        }
    }

    public function show(Course $course)
    {
        return Inertia::render('Instructor/Course/Show', [
            'course' => [
                'title' => $course->title,
                'slug' => $course->slug,
                'value' => $course->value,
                'instructor' => $course->instructor->name,
                'short_description' => $course->short_description,
                'description' => $course->description,
                'thumbnail' => $course->thumbnail_src,
                'total_participant' => $course->total_participant,
                'total_passed' => $course->total_passed,
                'efektifitas' => $course->efektifitas,
            ],
            'activities' => $this->activity->getActivityByCourse($course->id)
        ]);
    }

    public function edit(Course $course)
    {
        return Inertia::render('Instructor/Course/Edit', [
            'course' => $course,
            'employee_status' => Course::EMPLOYEE_STATUS,
            'schedules' => $course->load('schedules')->schedules->map(function ($row) {
                return [
                    'id' => $row->id,
                    'date' => $row->date,
                    'time_from' => $row->time_from,
                    'time_to' => $row->time_to,
                    'quota' => $row->quota,
                ];
            }),
            'categories' => Category::all()
        ]);
    }

    public function update(Course $course, Request $request)
    {
        if (is_string($request->schedules)) {
            $request->merge([
                'schedules' => json_decode($request->schedules, true)  // Dekode menjadi array
            ]);
        }

        $request->validate([
            'type' => 'required|string',
            'category' => 'required|numeric',
            'employee_status' => 'required|in:PNS,CPNS,PPPK,THL',
            'title' => 'required|string',
            'short_description' => 'required|string',
            'description' => 'required|string',
            'thumbnail' => 'nullable|image|max:10240|mimes:png,jpg',
            'teaser_video' => 'nullable|string',
            'expired' => 'required_if:type,unschedule',
            'status' => 'required|string',
            'jam_pelajaran' => 'required|string',
            'schedules' => 'required_if:type,schedule|array|min:1'
        ]);

        try {
            DB::beginTransaction();

            $this->course->updateCourse(
                $course,
                $this->instructorId,
                $request->only('type', 'category', 'employee_status', 'title', 'short_description', 'description', 'thumbnail', 'teaser_video', 'expired', 'status', 'jam_pelajaran'),
                $request->schedules
            );

            DB::commit();

            return to_route('instructor.dashboard')->with('success', 'Course updated successfully.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('instructor.dashboard')->with('danger', 'Something went wrong.');
        }
    }

    public function destroy(Course $course)
    {
        $course->delete();

        return to_route('instructor.dashboard')->with('success', 'Course deleted successfully.');
    }
}
