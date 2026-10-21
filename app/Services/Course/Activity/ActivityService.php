<?php

namespace App\Services\Course\Activity;

use App\Repositories\Course\Activity\Assessment\Contracts\QuestionRepositoryInterface;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionVariantRepositoryInterface;
use App\Repositories\Course\Activity\Contracts\ActivityRepositoryInterface;
use App\Repositories\Course\Activity\Contracts\InteractiveRepositoryInterface;
use App\Repositories\Learning\Contracts\LearnRepositoryInterface;

class ActivityService
{
    public function __construct(
        private readonly QuestionService $question,
        private readonly TaskService $task,
        private readonly InteractiveService $interactive,
        private readonly LiteratureService $literature,
        private readonly ActivityRepositoryInterface $activities,
        private readonly InteractiveRepositoryInterface $interactives,
        private readonly QuestionRepositoryInterface $questions,
        private readonly QuestionVariantRepositoryInterface $variants,
        private readonly LearnRepositoryInterface $learns,
    ) {}

    public function getActivityByCourse($courseId)
    {
        return $this->activities->getByCourse($courseId, ['modifier:id,name'])
            ->map(function ($row) {
                $endMeeting = null;
                if ($row->type == 'meeting') {
                    $endMeeting = $this->interactives->findByActivity($row->id)?->end_meeting ?? 0;
                }

                // $hasImported = null;
                // if ($row->type == 'pre-test' || $row->type == 'post-test') {
                //     $hasImported = Question::where('activity_id', $row->id)->first()->has_imported;
                // }

                $hasImported = false;

                if (in_array($row->type, ['pre-test', 'post-test'])) {

                    $question = $this->questions->findByActivity($row->id);

                    if ($question) {
                        $hasImported =
                            $question->has_imported == 1
                            || $this->variants->getByQuestion($question->id)->isNotEmpty();
                    }
                }

                return [
                    'id' => $row->id,
                    'course_id' => $row->course_id,
                    'type' => $row->type,
                    'title' => $row->title,
                    'timer' => $row->timer,
                    'value' => $row->value,
                    'end_meeting' => $endMeeting,
                    'has_imported' => $hasImported,
                    'updated_at' => $row->updated_at?->toIso8601String(),
                    'modified_by' => $row->modified_by,
                    'modifier_name' => $row->modifier?->name,
                    'status' => 0,
                ];
            });
    }

    public function getActivityByParticipant($participantId, $courseId)
    {
        $enrollmentId = request()->attributes->get('participant_enrollment')?->id;
        $lockedActivities = \App\Models\Learning\LearnItem::query()
            ->whereNotNull('locked_at')
            ->whereHas('learn', function ($query) use ($participantId, $courseId, $enrollmentId) {
                $query->where('participant_id', $participantId)->where('course_id', $courseId);
                if ($enrollmentId) $query->where('enrollment_id', $enrollmentId);
            })->pluck('activity_id')->map(fn ($id) => (string) $id)->all();

        return $this->activities->getByCourse($courseId, ['learnItem'])
            ->map(function ($row) use ($participantId, $lockedActivities) {
                return [
                    'id' => $row->id,
                    'course_id' => $row->course_id,
                    'type' => $row->type,
                    'title' => $row->title,
                    'value' => $row->value,
                    'timer' => $row->timer,
                    'value' => $row->value,
                    'status' => $this->getStatus($participantId, $row->course_id, $row->id),
                    'locked' => in_array((string) $row->id, $lockedActivities, true),
                ];
            });
    }

    public function getStatus($participantId, $courseId, $activityId)
    {
        $learn = $this->learns->findByParticipantAndCourse($participantId, $courseId, [
            'learnItems' => fn ($query) => $query->where('activity_id', $activityId),
        ]);

        // dd($learn->learnItems);
        if (! $learn || ! $learn->learnItems->first()) {
            return 0;
        }

        return $learn->learnItems->first()->status;
    }

    public function createActivity($data)
    {
        if (isset($data['instruction'])) {
            $data['instruction'] = sanitize_html($data['instruction']);
        }

        return $this->activities->create($data);
    }

    public function findById($activityId)
    {
        return $this->activities->find($activityId);
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
            return $this->interactive->getVideo($activity);
        }

        if ($activity->type === 'literature') {
            return $this->literature->getLiterature($activity);
        }
    }

    public function getDetailActivityParticipant($participantId, $activityId)
    {
        $activity = $this->findById($activityId);

        if ($activity->type === 'pre-test') {
            return $this->question->getPreTestParticipant($participantId, $activity);
        }

        if ($activity->type === 'post-test') {
            return $this->question->getPostTestParticipant($participantId, $activity);
        }

        if ($activity->type === 'task') {
            return $this->task->getTask($participantId, $activity);
        }

        if ($activity->type === 'meeting') {
            return $this->interactive->getMeetingParticipant($participantId, $activity);
        }

        if ($activity->type === 'video') {
            return $this->interactive->getVideo($activity);
        }

        if ($activity->type === 'literature') {
            return $this->literature->getLiterature($activity);
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
            return $this->task->storeTaskParticipant($participantId, $courseId, $activityId, $request);
        }

        if ($activity->type == 'meeting') {
            return $this->interactive->storeMeeting($participantId, $courseId, $activityId, $request);
        }

        if ($activity->type == 'video') {
            return $this->interactive->storeVideo($participantId, $courseId, $activityId, $request);
        }

        if ($activity->type == 'literature') {
            return $this->literature->storeLiterature($participantId, $courseId, $activityId, $request);
        }
    }

    public function updateActivity($request)
    {
        $activity = $this->activities->findOrFail($request['activity_id']);

        $activity = $this->activities->update($activity, [
            'title' => $request['title'],
            'value' => $request['value'],
            'instruction' => isset($request['instruction']) ? sanitize_html($request['instruction']) : null,
            'updated_at' => now(),
        ]);

        if ($activity->type === 'pre-test' || $activity->type === 'post-test') {
            $activity = $this->activities->update($activity, ['timer' => $request['timer']]);
        }

        return $activity;
    }

    public function nextActivity($courseId, $activityId)
    {
        $activity = $this->activities->find($activityId);
        $nextActivity = $activity ? $this->activities->getNext($courseId, $activity->id) : null;
        // dd($nextActivity);
        if ($nextActivity) {
            return $nextActivity->id;
        } else {
            return null;
        }
    }

    // ActivityService.php
    public function getPreviousActivity($courseId, $currentActivityId)
    {
        $activities = $this->activities->getByCourse($courseId);
        $index = $activities->search(function ($activity) use ($currentActivityId) {
            return $activity->id == $currentActivityId;
        });

        return $index > 0 ? $activities[$index - 1] : null;
    }
}
