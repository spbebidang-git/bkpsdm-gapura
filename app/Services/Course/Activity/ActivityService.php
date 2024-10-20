<?php

namespace App\Services\Course\Activity;

use App\Models\Course\Activity;

class ActivityService
{
    protected $question, $task, $interactive, $literature;

    public function  __construct(
        QuestionService $questionService,
        TaskService $taskService,
        InteractiveService $interactiveService,
        LiteratureService $literatureService
    ) {
        $this->question = $questionService;
        $this->task = $taskService;
        $this->interactive = $interactiveService;
        $this->literature = $literatureService;
    }

    public function getActivityByCourse($courseId)
    {
        return Activity::with('learnItem')
            ->where('course_id', $courseId)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'course_id' => $row->course_id,
                    'type' => $row->type,
                    'title' => $row->title,
                    'timer' => $row->timer,
                    'value' => $row->value,
                    'status' => $row->learnItem && $row->learnItem->status == 'done' ? 1 : 0,
                ];
            });
    }

    public function createActivity($data)
    {
        return Activity::create($data);
    }

    public function findById($activityId)
    {
        return Activity::find($activityId);
    }

    public function getDetailActivity($activityId)
    {
        $activity = $this->findById($activityId);

        if ($activity->type === 'pre-test') {
            return $this->question->getPreTest($activity);
        }

        if ($activity->type === 'post-test') {
            return $this->question->getPostTest($activity);
        }

        if ($activity->type === 'task') {
            return $this->task->getTask();
        }

        if ($activity->type === 'meeting') {
            return $this->interactive->getMeeting();
        }

        if ($activity->type === 'video') {
            return $this->interactive->getVideo($activityId);
        }

        if ($activity->type === 'literature') {
            return $this->literature->getLiterature();
        }
    }

    public function storeActivity($participantId, $courseId, $activityId, $request)
    {
        $activity = $this->findById($activityId);

        if ($activity->type == 'pre-test') {
            return $this->question->storePreTest($participantId, $courseId, $activityId, $request);
        }

        if ($activity->type == 'post-test') {
            return $this->question->storePostTest($participantId, $courseId, $activityId, $request);
        }

        if ($activity->type == 'task') {
            return $this->task->storeTask($activityId, $request);
        }

        if ($activity->type == 'meeting') {
            return $this->interactive->storeMeeting($activityId, $request);
        }

        if ($activity->type == 'video') {
            return $this->interactive->storeVideo($participantId, $courseId, $activityId, $request);
        }

        if ($activity->type == 'literature') {
            return $this->literature->storeLiterature($activityId, $request);
        }
    }

    public function updateActivity($request)
    {
        $activity = Activity::find($request['activity_id']);

        $activity->update([
            'title' => $request['title'],
            'value' => $request['value'],
        ]);

        return $activity;
    }

    public function nextActivity($courseId, $activityId)
    {
        $activity = Activity::where('id', $activityId)->first();
        $nextActivity = Activity::where('id', '>', $activity->id)
            ->where('course_id', $courseId)
            ->orderBy('id', 'asc')
            ->first();
        // dd($nextActivity);
        if ($nextActivity) {
            return $nextActivity->id;
        } else {
            return null;
        }
    }
}
