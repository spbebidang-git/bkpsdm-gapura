<?php

namespace App\Http\Controllers\Instructor\Course\Literature;

use App\Models\Course\Activity;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Course\Course;
use Illuminate\Support\Facades\DB;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\Activity\LiteratureService;

class LiteratureController
{
    protected $activity, $literature;

    public function __construct(
        ActivityService $activityService,
        LiteratureService $literatureService
    ) {
        $this->activity = $activityService;
        $this->literature = $literatureService;
    }

    public function create($slug)
    {
        return Inertia::render('Instructor/Course/Literature/Create', [
            'slug' => $slug
        ]);
    }

    public function store(Request $request, Course $course)
    {
        $request->validate([
            'title' => 'required',
            'sub_title' => 'required',
            'value' => 'required|numeric',
            'duration' => 'required|numeric',
            'content' => 'required|mimes:pdf|max:10240',
        ]);

        try {
            DB::beginTransaction();

            # Insert Activity
            $activity = $this->activity->createActivity([
                'course_id' => $course->id,
                'type' => 'literature',
                'title' => $request->title,
                'value' => $request->value,
            ]);

            # Insert Literature
            $this->literature->createLiterature([
                'activity_id' => $activity->id,
                'sub_title' => $request->sub_title,
                'content' => $request->content,
                'duration' => $request->duration,
            ]);

            DB::commit();

            return to_route('instructor.course.show', $course->slug)->with('success', 'Literature created successfully.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('instructor.course.show', $course->slug)->with('danger', 'Something went wrong.');
        }
    }

    public function edit($slug, Activity $literature)
    {
        return Inertia::render('Instructor/Course/Literature/Edit', [
            'slug' => $slug,
            'activity' => $this->literature->getLiteratureForEdit($literature),
        ]);
    }

    public function update(Course $course, Activity $literature, Request $request)
    {
        $request->validate([
            'title' => 'required',
            'sub_title' => 'required',
            'value' => 'required|numeric',
            'content' => 'required|mimes:pdf|max:10240',
        ]);

        try {
            DB::beginTransaction();

            # Update Activity
            $activity = $this->activity->updateActivity([
                'activity_id' => $literature->id,
                'title' => $request->title,
                'value' => $request->value,
            ]);

            # Update Pre Test
            $this->literature->updateLiterature([
                'activity_id' => $activity->id,
                'sub_title' => $request->sub_title,
                'content' => $request->content,
                'duration' => $request->duration,
            ]);

            DB::commit();

            return to_route('instructor.course.show', $course->slug)->with('success', 'Literature updated successfully.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('instructor.course.show', $course->slug)->with('danger', $th->getMessage());
        }
    }
}
