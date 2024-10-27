<?php

namespace App\Services\Course\Activity;

use App\Models\Course\Activity;
use App\Models\Course\Interactive;
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

    public function getMeetingParticipant($activity)
    {
        return [
            'activity_id' => $activity->id,
            'title' => $activity->title,
            'type' => 'meeting',
            'status' => $activity->learnItem ? $activity->learnItem->status : 0,
            'data' => [
                'meeting_url' => 'https://www.youtube.com/embed/M66U_DuMCS8',
                'duration' => 120
            ]
        ];
    }

    public function getVideo($activity)
    {

        return [
            'activity_id' => $activity->id,
            'title' => $activity->title,
            'type' => 'video',
            'status' => $activity->learnItem && $activity->learnItem->status == 'done' ? 1 : 0,
            'data' => [
                'src_video' => 'https://www.youtube.com/embed/M66U_DuMCS8',
                'duration' => 120
            ]
        ];
    }

    public function storeMeeting($participantId, $courseId, $activityId, $request)
    {
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

        $this->learn->createLearn($participantId, $courseId, $activityId, null, $learnItem);
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
            'description' => $interactive->description,
            'url' => $interactive->url,
            'duration' => $interactive->duration,
        ];
    }
}
