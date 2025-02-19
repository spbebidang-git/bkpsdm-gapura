<?php

namespace App\Services\Course\Activity;

use App\Models\Course\Activity;
use App\Models\Course\Interactive;
use App\Models\Learn\Learn;
use App\Services\Learn\LearnService;

class InteractiveService
{
    protected $learn;

    public function __construct(LearnService $learnService)
    {
        $this->learn = $learnService;
    }

    public function createInteractive($data)
    {
        return Interactive::create($data);
    }

    public function getMeeting($activity)
    {
        return [
            'activity_id' => $activity->id,
            'type' => 'meeting',
            'data' => []
        ];
    }

    public function getStatus($participantId, $activityId)
    {
        $learn = Learn::with(['learnItems' => function ($query) use ($activityId) {
            return $query->where('activity_id', $activityId);
        }])
            ->where('participant_id', $participantId)->first();

        if (!$learn || !$learn->learnItems->first()) {
            return 0;
        }

        return $learn->learnItems->first()->status;
    }

    public function getMeetingParticipant($participantId, $activity)
    {
        $interactive = Interactive::where('activity_id', $activity->id)->first();

        return [
            'activity_id' => $activity->id,
            'title' => $activity->title,
            'type' => 'meeting',
            'status' => $this->getStatus($participantId, $activity->id),
            'data' => [
                'meeting_url' => $interactive->url,
                'duration' => $interactive->duration
            ]
        ];
    }

    public function getVideo($activity)
    {
        $interactive = Interactive::where('activity_id', $activity->id)->first();
        return [
            'activity_id' => $activity->id,
            'title' => $activity->title,
            'type' => 'video',
            'status' => $activity->learnItem && $activity->learnItem->status == 'done' ? 1 : 0,
            'data' => [
                'src_video' => $interactive->url,
                'duration' => $interactive->duration
            ]
        ];
    }

    public function storeMeeting($participantId, $courseId, $activityId, $request)
    {
        $interactive = Interactive::where('activity_id', $activityId)->first();
        // dd($request);

        if ($request) {
            $learnItem = [
                'type' => 'meeting',
                'status' => $request['status'],
                'response' => null
            ];
        } else {
            $learnItem = [
                'type' => 'meeting',
                'status' => 'done',
                'response' => null
            ];
        }

        if ($request && $request['status'] == 'done') {
            if (!$interactive->end_meeting) {
                return false;
            }
        }

        $this->learn->createLearn($participantId, $courseId, $activityId, null, $learnItem);

        return true;
    }

    public function storeVideo($participantId, $courseId, $activityId, $request)
    {
        $learnItem = [
            'type' => 'video',
            'status' => 'done',
            'response' => null
        ];

        $this->learn->createLearn($participantId, $courseId, $activityId, null, $learnItem);
    }

    public function updateInteractive($request)
    {
        $interactive = Interactive::where('activity_id', $request['activity_id'])->first();

        $interactive->update([
            'description' => $request['description'],
            'url' => $request['url'],
            'duration' => $request['duration'],
        ]);

        return true;
    }

    public function getInteractive($activity)
    {
        $interactive = Interactive::where('activity_id', $activity->id)->first();

        return [
            'id' => $activity->id,
            'title' => $activity->title,
            'value' => $activity->value,
            'instruction' => $activity->instruction,
            'description' => $interactive->description,
            'url' => $interactive->url,
            'duration' => $interactive->duration,
        ];
    }
}
