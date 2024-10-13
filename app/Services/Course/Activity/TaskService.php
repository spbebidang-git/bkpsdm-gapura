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

    public function storeTask() {}
}
