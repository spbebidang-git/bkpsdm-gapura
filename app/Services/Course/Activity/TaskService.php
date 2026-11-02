<?php

namespace App\Services\Course\Activity;

use App\Helper\UploadMedia;
use App\Models\Course\Activity\Task;
use App\Repositories\Course\Activity\Contracts\TaskRepositoryInterface;
use App\Repositories\Learning\Contracts\LearnItemRepositoryInterface;
use App\Services\Learning\LearnService;
use Illuminate\Support\Facades\File;

class TaskService
{
    public function __construct(
        private readonly LearnService $learn,
        private readonly TaskRepositoryInterface $tasks,
        private readonly LearnItemRepositoryInterface $items,
    ) {}

    public function createTask($data)
    {
        return $this->tasks->create(array_merge($data, ['is_calculated' => false]));
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

        $this->tasks->update($task, [
            'description' => $request['description'],
            'is_calculated' => false,
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

    public function results(string $activityId)
    {
        $tasks = $this->items->taskResults($activityId)->map(function ($row) {
            // dd($row);
            return [
                'nip' => get_user_meta($row->learn->participant->id, 'nip'),
                'name' => $row->learn->participant->name,
                'file' => asset(Task::$directoryTask.unserialize($row->response)),
            ];
        });

        return $tasks;
    }
}
