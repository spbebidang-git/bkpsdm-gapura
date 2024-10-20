<?php

namespace App\Services\Course\Activity;

use App\Models\Course\Task;

class TaskService
{
    public function createTask($data)
    {
        return Task::create($data);
    }

    public function getTask()
    {
        return [
            'activity_id' => 7,
            'type' => 'task',
            'data' => [
                'title' => 'title',
                'description' => 'description'
            ]
        ];
    }

    public function updateTask($request)
    {
        $task = Task::where('activity_id', $request['activity_id'])->first();

        $task->update([
            'description' => $request['description']
        ]);

        return true;
    }

    public function getTaskForEdit($activity)
    {
        $task = Task::where('activity_id', $activity->id)->first();

        return [
            'id' => $activity->id,
            'title' => $activity->title,
            'value' => $activity->value,
            'description' => $task->description,
        ];
    }
}
