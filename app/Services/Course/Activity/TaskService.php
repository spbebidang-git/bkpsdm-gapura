<?php

namespace App\Services\Course\Activity;

use App\Helper\UploadMedia;
use App\Models\Course\Activity\Task;
use App\Repositories\Course\Activity\Contracts\TaskRepositoryInterface;
use App\Services\Learning\LearnService;
use Illuminate\Support\Facades\File;

class TaskService
{
    public function __construct(
        private readonly LearnService $learn,
        private readonly TaskRepositoryInterface $tasks,
    ) {}

    public function createTask($data)
    {
        return $this->tasks->create($data);
    }

    public function getTask($participantId, $activity)
    {
        $task = $this->tasks->findByActivity($activity->id);

        return [
            'activity_id' => $activity->id,
            'title' => $activity->title,
            'type' => 'task',
            // 'status' => 'done',
            'data' => [
                'title' => $task->sub_title,
                'description' => $task->description,
            ],
        ];
    }

    public function updateTask($request)
    {
        $task = $this->tasks->findByActivity($request['activity_id']);

        $task->update([
            'description' => $request['description'],
            'is_calculated' => $request['is_calculated'],
        ]);

        return true;
    }

    public function getTaskForEdit($activity)
    {
        $task = $this->tasks->findByActivity($activity->id);

        return [
            'id' => $activity->id,
            'title' => $activity->title,
            'value' => $activity->value,
            'description' => $task?->description ?? '',
            'is_calculated' => $task?->is_calculated ?? false,
            'instruction' => $activity->instruction,
        ];
    }

    public function storeTaskParticipant($participantId, $courseId, $activityId, $request)
    {
        $directory = public_path(Task::$directoryTask);
        File::ensureDirectoryExists($directory);
        $learnItem = [
            'type' => 'task',
            'status' => 'done',
            'response' => UploadMedia::file($request['file'], $directory),
        ];

        $this->learn->createLearn($participantId, $courseId, $activityId, $learnItem);
    }
}
