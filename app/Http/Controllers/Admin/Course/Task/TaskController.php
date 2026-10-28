<?php

namespace App\Http\Controllers\Admin\Course\Task;

use App\Models\Course\Activity\Activity;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Course\Course;
use App\Models\Course\Activity\Task;
use App\Models\Learning\LearnItem;
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

    public function create(Course $course)
    {
        return Inertia::render('Instructor/Course/Task/Create', [
            'slug' => $course->slug
        ]);
    }

    public function store(Request $request, Course $course)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'value' => 'required|numeric'
        ]);

        try {
            DB::beginTransaction();

            # Insert Activity
            $activity = $this->activity->createActivity([
                'course_id' => $course->id,
                'type' => 'task',
                'title' => $request->title,
                'value' => $request->value,
                'instruction' => $request->instruction,
            ]);

            # Insert Pre Test
            $this->task->createTask([
                'activity_id' => $activity->id,
                'description' => $request->description,
            ]);

            DB::commit();

            return to_route('admin.course.activities.index', $course->slug)->with('success', 'Tugas berhasil ditambahkan.');
        } catch (\Throwable $th) {
            DB::rollBack();
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

    public function update(Course $course, Activity $task, Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'value' => 'required|numeric'
        ]);

        try {
            DB::beginTransaction();

            # Update Activity
            $activity = $this->activity->updateActivity([
                'activity_id' => $task->id,
                'title' => $request->title,
                'value' => $request->value,
                'instruction' => $request->instruction,
            ]);

            # Update Pre Test
            $this->task->updateTask([
                'activity_id' => $task->id,
                'description' => $request->description,
            ]);

            DB::commit();

            return to_route('admin.course.activities.index', $course->slug)->with('success', 'Tugas berhasil diperbarui.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return to_route('admin.course.activities.index', $course->slug)->with('danger', 'Terjadi kesalahan saat menyimpan data.');
        }
    }

    public function result(Course $course, $activityId)
    {
        $tasks = LearnItem::with('learn.participant')->where('activity_id', $activityId)->get()->map(function ($row) {
            // dd($row);
            return [
                'nip' => get_user_meta($row->learn->participant->id, 'nip'),
                'name' => $row->learn->participant->name,
                'file' => asset(Task::$directoryTask . unserialize($row->response)),
            ];
        });

        return Inertia::render('Instructor/Course/Task/Result', [
            'slug' => $course->slug,
            'activity' => $activityId,
            'tasks' => $tasks
        ]);
    }
}
