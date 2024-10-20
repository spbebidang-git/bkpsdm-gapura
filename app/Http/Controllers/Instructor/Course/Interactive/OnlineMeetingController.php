<?php

namespace App\Http\Controllers\Instructor\Course\Interactive;

use App\Models\Course\Activity;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Course\Course;
use App\Services\Course\Activity\ActivityService;
use Illuminate\Support\Facades\DB;
use App\Services\Course\Activity\InteractiveService;

class OnlineMeetingController
{
    protected $activity, $interactive;

    public function __construct(
        ActivityService $activityService,
        InteractiveService $interactiveService
    ) {
        $this->activity = $activityService;
        $this->interactive = $interactiveService;
    }

    public function create($slug)
    {
        return Inertia::render('Instructor/Course/Meeting/Create', [
            'slug' => $slug
        ]);
    }

    public function store(Request $request, Course $course)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'value' => 'required|numeric',
            'url' => 'required|string',
            'duration' => 'required|numeric',
        ]);

        try {
            DB::beginTransaction();

            # Insert Activity
            $activity = $this->activity->createActivity([
                'course_id' => $course->id,
                'type' => 'meeting',
                'title' => $request->title,
                'value' => $request->value,
            ]);

            # Insert Pre Test
            $this->interactive->createInteractive([
                'activity_id' => $activity->id,
                'type' => 'meeting',
                'description' => $request->description,
                'url' => $request->url,
                'duration' => $request->duration
            ]);

            DB::commit();

            return to_route('instructor.course.show', $course->slug);
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);
            return to_route('instructor.course.show', $course->slug);
        }
    }

    public function edit($slug, Activity $meeting)
    {
        return Inertia::render('Instructor/Course/Meeting/Edit', [
            'slug' => $slug,
            'activity' => $this->interactive->getInteractive($meeting),
        ]);
    }

    public function update(Course $course, Activity $meeting, Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'value' => 'required|numeric',
            'url' => 'required|string',
            'duration' => 'required|numeric',
        ]);

        try {
            DB::beginTransaction();

            # Update Activity
            $activity = $this->activity->updateActivity([
                'activity_id' => $meeting->id,
                'title' => $request->title,
                'value' => $request->value,
            ]);

            # Update Pre Test
            $this->interactive->updateInteractive([
                'activity_id' => $meeting->id,
                'type' => 'meeting',
                'description' => $request->description,
                'url' => $request->url,
                'duration' => $request->duration
            ]);

            DB::commit();

            return to_route('instructor.course.show', $course->slug);
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('instructor.course.show', $course->slug);
        }
    }
}
