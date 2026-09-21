<?php

namespace App\Services\Course\Activity;

use App\Repositories\Course\Activity\Contracts\InteractiveRepositoryInterface;
use App\Repositories\Learning\Contracts\LearnRepositoryInterface;
use App\Services\Learning\LearnService;

class InteractiveService
{
    public function __construct(
        private readonly LearnService $learn,
        private readonly InteractiveRepositoryInterface $interactives,
        private readonly LearnRepositoryInterface $learns,
    ) {}

    public function createInteractive($data)
    {
        return $this->interactives->create($data);
    }

    public function getMeeting($activity)
    {
        return [
            'activity_id' => $activity->id,
            'type' => 'meeting',
            'data' => [],
        ];
    }

    public function getStatus($participantId, $activityId)
    {
        $learn = $this->learns->findByParticipant($participantId, [
            'learnItems' => fn ($query) => $query->where('activity_id', $activityId),
        ]);

        if (! $learn || ! $learn->learnItems->first()) {
            return 0;
        }

        return $learn->learnItems->first()->status;
    }

    public function getMeetingParticipant($participantId, $activity)
    {
        $interactive = $this->interactives->findByActivity($activity->id);
        $learn = $this->learns->findByParticipantAndCourse($participantId, $activity->course_id, [
            'learnItems' => fn ($query) => $query->where('activity_id', $activity->id),
        ]);
        $status = null;
        if (! $learn || ! $learn->learnItems->first()) {
            $status = 0;
        } else {
            $status = $learn->learnItems->first()->status;
        }

        return [
            'activity_id' => $activity->id,
            'title' => $activity->title,
            'type' => 'meeting',
            'status' => $status,
            'data' => [
                'meeting_url' => $interactive->url,
                'duration' => $interactive->duration,
            ],
        ];
    }

    public function getVideo($activity)
    {
        $interactive = $this->interactives->findByActivity($activity->id);

        return [
            'activity_id' => $activity->id,
            'title' => $activity->title,
            'type' => 'video',
            'status' => $activity->learnItem && $activity->learnItem->status == 'done' ? 1 : 0,
            'data' => [
                'src_video' => $interactive->url,
                'duration' => $interactive->duration,
            ],
        ];
    }

    public function storeMeeting($participantId, $courseId, $activityId, $request)
    {
        $interactive = $this->interactives->findByActivity($activityId);

        if ($request) {
            $learnItem = [
                'type' => 'meeting',
                'status' => $request['status'],
                'response' => null,
            ];
        } else {
            $learnItem = [
                'type' => 'meeting',
                'status' => 'done',
                'response' => null,
            ];
        }

        if ($request && $request['status'] == 'done') {
            if (! $interactive->end_meeting) {
                return false;
            }
        }

        $this->learn->createLearn($participantId, $courseId, $activityId, $learnItem);

        return true;
    }

    public function storeVideo($participantId, $courseId, $activityId, $request)
    {
        $learnItem = [
            'type' => 'video',
            'status' => 'done',
            'response' => null,
        ];

        $this->learn->createLearn($participantId, $courseId, $activityId, $learnItem);
    }

    public function updateInteractive($request)
    {
        $interactive = $this->interactives->findByActivity($request['activity_id']);

        $interactive->update([
            'description' => $request['description'],
            'url' => $request['url'],
            'duration' => $request['duration'],
        ]);

        return true;
    }

    public function getInteractive($activity)
    {
        $interactive = $this->interactives->findByActivity($activity->id);

        return [
            'id' => $activity->id,
            'title' => $activity->title,
            'value' => $activity->value,
            'instruction' => $activity->instruction,
            'description' => $interactive?->description ?? '',
            'url' => $interactive?->url ?? '',
            'duration' => $interactive?->duration ?? 0,
        ];
    }
}
