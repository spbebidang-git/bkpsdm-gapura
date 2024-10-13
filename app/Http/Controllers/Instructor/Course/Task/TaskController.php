<?php

namespace App\Http\Controllers\Instructor\Course\Task;

use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Course\Course;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\Activity\TaskService;
use Illuminate\Support\Facades\DB;

class TaskController
{
    protected $activity, $task;

    public function __construct(
        ActivityService $activityService,
        TaskService $taskService
    ) {
        $this->activity = $activityService;
        $this->task = $taskService;
    }

    public function create($slug)
    {
        return Inertia::render('Instructor/Course/Task/Create', [
            'slug' => $slug
        ]);
    }

    public function store(Request $request, Course $course)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'required',
        ]);

        try {
            DB::beginTransaction();

            # Insert Activity
            $activity = $this->activity->createActivity([
                'course_id' => $course->id,
                'type' => 'task',
                'title' => $request->title,
            ]);

            # Insert Pre Test
            $this->task->createTask([
                'activity_id' => $activity->id,
                'description' => $request->description,
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
