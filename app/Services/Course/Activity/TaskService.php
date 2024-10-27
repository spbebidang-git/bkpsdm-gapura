<?php

namespace App\Services\Course\Activity;

use App\Helper\UploadMedia;
use App\Models\Course\Task;
use App\Services\Learn\LearnService;

class TaskService
{
    protected $learn;

    public function __construct(LearnService $learnService)
    {
        $this->learn = $learnService;
    }

    public function createTask($data)
    {
        return Task::create($data);
    }

    public function getTask($activity)
    {
        return [
            'activity_id' => 7,
            'title' => $activity->title,
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

    public function storeTaskParticipant($participantId, $courseId, $activityId, $request)
    {
        $learnItem = [
            'type' => 'task',
            'status' => 'done',
            'response' =>  UploadMedia::file($request['file'], Task::$directoryTask)
        ];

        $this->learn->createLearn($participantId, $courseId, $activityId, null, $learnItem);
    }
}
