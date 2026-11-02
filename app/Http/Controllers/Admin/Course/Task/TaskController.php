<?php

namespace App\Http\Controllers\Admin\Course\Task;

use App\Http\Requests\Admin\TaskStoreRequest;
use App\Http\Requests\Admin\TaskUpdateRequest;
use App\Models\Course\Activity\Activity;
use App\Models\Course\Course;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\Activity\AdminActivityService;
use App\Services\Course\Activity\TaskService;
use Inertia\Inertia;

class TaskController
{
    protected $activity;

    protected $task;

    public function __construct(
        ActivityService $activityService,
        TaskService $taskService,
        private readonly AdminActivityService $management,
    ) {
        $this->activity = $activityService;
        $this->task = $taskService;
    }

    public function create(Course $course)
    {
        return Inertia::render('Instructor/Course/Task/Create', [
            'slug' => $course->slug,
        ]);
    }

    public function store(TaskStoreRequest $request, Course $course)
    {
        try {
            $this->management->storeTask($request->validated() + $request->only(['instruction', 'repeatable', 'show_result']), $course);

            return to_route('admin.course.activities.index', $course->slug)->with('success', 'Tugas berhasil ditambahkan.');
        } catch (\Throwable $th) {

            return to_route('admin.course.activities.index', $course->slug)->with('danger', 'Terjadi kesalahan saat menyimpan data.');
        }
    }

    public function edit(Course $course, Activity $task)
    {
        return Inertia::render('Instructor/Course/Task/Edit', [
            'slug' => $course->slug,
            'activity' => $this->task->getTaskForEdit($task),
        ]);
    }

    public function update(Course $course, Activity $task, TaskUpdateRequest $request)
    {
        try {
            $this->management->updateTask($course, $task, $request->validated() + $request->only(['instruction', 'repeatable', 'show_result']));

            return to_route('admin.course.activities.index', $course->slug)->with('success', 'Tugas berhasil diperbarui.');
        } catch (\Throwable $th) {

            return to_route('admin.course.activities.index', $course->slug)->with('danger', 'Terjadi kesalahan saat menyimpan data.');
        }
    }

    public function result(Course $course, $activityId)
    {
        $tasks = $this->task->results($activityId);

        return Inertia::render('Instructor/Course/Task/Result', [
            'slug' => $course->slug,
            'activity' => $activityId,
            'tasks' => $tasks,
        ]);
    }
}
