<?php

namespace App\Services\Course\Activity;

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

    public function getVideo()
    {
        return [
            'activity_id' => 7,
            'type' => 'video',
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
}
