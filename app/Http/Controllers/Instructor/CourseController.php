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
            'categories' => Category::all()
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required',
            'category' => 'required',
            'title' => 'required',
            'short_description' => 'required',
            'description' => 'required',
            'thumbnail' => 'required|image|mimes:png.jpg|max:1024',
            'teaser_video' => 'required',
        ]);

        try {
            DB::beginTransaction();

            $this->course->createCourse(
                $this->instructorId,
                $request->only('type', 'category', 'title', 'short_description', 'description', 'thumbnail', 'teaser_video'),
                json_decode($request->schedules, true)
            );

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
        }
    }

    public function show(Course $course)
    {
        return Inertia::render('Instructor/Course/Show', [
            'course' => [
                'title' => $course->title,
                'slug' => $course->slug,
                'instructor' => $course->instructor->name,
                'short_description' => $course->short_description,
                'description' => $course->description,
                'thumbnail' => $course->thumbnail
            ],
            'activities' => $this->activity->getActivityByCourse($course->id)
        ]);
    }

    public function edit(Course $course)
    {
        return Inertia::render('Instructor/Course/Edit', [
            'course' => $course,
            'categories' => Category::all()
        ]);
    }

    public function update(Course $course, Request $request)
    {
        dd($request->all());

        $request->validate([
            'type' => 'required',
            'category' => 'required',
            'title' => 'required',
            'short_description' => 'required',
            'description' => 'required',
            'thumbnail' => 'nullable|image|mimes:png.jpg|max:1024',
            'teaser_video' => 'required',
        ]);


        try {
            DB::beginTransaction();

            $this->course->updateCourse(
                $course,
                $this->instructorId,
                $request->only('type', 'category', 'title', 'short_description', 'description', 'thumbnail', 'teaser_video'),
                json_decode($request->schedules, true)
            );

            DB::commit();

            return to_route('instructor.dashboard');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('instructor.dashboard');
        }
    }

    public function destroy(Course $course)
    {
        $course->delete();
    }
}
