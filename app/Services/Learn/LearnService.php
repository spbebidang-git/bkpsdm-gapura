<?php

namespace App\Services\Learn;

use App\Models\Learn\Learn;
use App\Models\Learn\LearnItem;

class LearnService
{
    public function createLearn($participantId, $courseId, $activityId, $scheduleId = null, $learnItems)
    {
        $learn = Learn::where('participant_id', $participantId)
            ->where('course_id', $courseId)
            ->first();

        if (!$learn) {
            $learn = Learn::create([
                'participant_id' => $participantId,
                'course_id' => $courseId,
            ]);
        }

        $this->createLearnItem($learn->id, $activityId, $learnItems);
    }

    public function createLearnItem($learnId, $activityId, $learnItems)
    {
        return LearnItem::create([
            'learn_id' => $learnId,
            'activity_id' => $activityId,

            # dinamyc items
            'title' => 'title',
            'type' => $learnItems['type'],
            'response' => $learnItems['response'] ? serialize($learnItems['response']) : null,
        ]);
    }
}
