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
        return Activity::with('question')
            ->where('course_id', $courseId)
            ->get();
        // ->map(function ($row) {
        //     return [
        //         $row->questions
        //     ];
        // });
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
        // dd($activity);
        if ($activity->type === 'pre-test') {
            return $this->question->getPreTest($activityId);
        }

        if ($activity->type === 'post-test') {
            return $this->question->getPostTest($activityId);
        }

        if ($activity->type === 'task') {
            return $this->task->getTask();
        }

        if ($activity->type === 'meeting') {
            return $this->interactive->getMeeting();
        }

        if ($activity->type === 'video') {
            return $this->interactive->getVideo();
        }

        if ($activity->type === 'literature') {
            return $this->literature->getLiterature();
        }
    }

    public function storeActivity($participantId, $courseId, $activityId, $request)
    {
        $activity = $this->findById($activityId);
        // dd($activity);

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
}
