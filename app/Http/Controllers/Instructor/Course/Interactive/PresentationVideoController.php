<?php

namespace App\Http\Controllers\Instructor\Course\Interactive;

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

    public function create($slug)
    {
        return Inertia::render('Instructor/Course/VideoPresentation/Create', [
            'slug' => $slug
        ]);
    }

    public function store(Request $request, Course $course)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'required',
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

            return to_route('instructor.course.show', $course->slug);
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);
            return to_route('instructor.course.show', $course->slug);
        }
    }
}
