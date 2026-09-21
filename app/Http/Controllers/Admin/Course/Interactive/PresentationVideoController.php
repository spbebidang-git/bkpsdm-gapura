<?php

namespace App\Http\Controllers\Admin\Course\Interactive;

use App\Models\Course\Activity\Activity;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Course\Course;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\Activity\InteractiveService;
use Illuminate\Support\Facades\DB;

class PresentationVideoController
{
    protected $activity, $interactive;

    public function __construct(
        ActivityService $activityService,
        InteractiveService $interactiveService
    ) {
        $this->activity = $activityService;
        $this->interactive = $interactiveService;
    }

    public function create(Course $course)
    {
        return Inertia::render('Instructor/Course/VideoPresentation/Create', [
            'slug' => $course->slug
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
                'type' => 'video',
                'title' => $request->title,
                'value' => $request->value,
                'instruction' => $request->instruction,
            ]);

            # Insert Pre Test
            $this->interactive->createInteractive([
                'activity_id' => $activity->id,
                'type' => 'video',
                'description' => $request->description,
                'url' => $request->url,
                'duration' => $request->duration
            ]);

            DB::commit();

            return to_route('admin.course.show', $course->slug)->with('success', 'Video created successfully.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('admin.course.show', $course->slug)->with('danger', 'Something went wrong.');
        }
    }

    public function edit(Course $course, Activity $video)
    {
        return Inertia::render('Instructor/Course/VideoPresentation/Edit', [
            'slug' => $course->slug,
            'activity' => $this->interactive->getInteractive($video),
        ]);
    }

    public function update(Course $course, Activity $video, Request $request)
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
                'activity_id' => $video->id,
                'title' => $request->title,
                'value' => $request->value,
                'instruction' => $request->instruction,
            ]);

            # Update Pre Test
            $this->interactive->updateInteractive([
                'activity_id' => $video->id,
                'type' => 'video',
                'description' => $request->description,
                'url' => $request->url,
                'duration' => $request->duration
            ]);

            DB::commit();

            return to_route('admin.course.show', $course->slug)->with('success', 'Video updated successfully.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('admin.course.show', $course->slug)->with('danger', 'Something went wrong.');
        }
    }
}
