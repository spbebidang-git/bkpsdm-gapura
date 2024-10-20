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

    public function getMeeting()
    {
        return [
            'activity_id' => 7,
            'type' => 'meeting',
            'data' => []
        ];
    }

    public function getVideo($activityId)
    {
        $activity = Activity::with('learnItem')
            ->where('id', $activityId)
            ->first();

        return [
            'activity_id' => $activityId,
            'type' => 'video',
            'status' => $activity->learnItem && $activity->learnItem->status == 'done' ? 1 : 0,
            'data' => [
                'src_video' => 'https://www.youtube.com/embed/M66U_DuMCS8',
                'duration' => 120
            ]
        ];
    }

    public function storeMeeting() {}

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
