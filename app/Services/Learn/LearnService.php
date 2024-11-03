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
        // dd($learnItems['response']);
        return LearnItem::updateOrCreate(
            [
                'learn_id' => $learnId,
                'activity_id' => $activityId
            ],
            [
                'title' => 'title',
                'type' => $learnItems['type'],
                'response' => $learnItems['response'] ? serialize($learnItems['response']) : null,
                'status' => $learnItems['status']
            ]
        );
    }

    public function getResultQuestion($participantId, $courseId, $activityId)
    {
        // $response = 'a:1:{i:0;a:4:{s:8:"question";s:10:"Question 1";s:13:"option_select";s:5:"benar";s:5:"score";i:100;s:10:"is_correct";b:1;}}';
        $learn = Learn::with(['learnItems' => function ($query) use ($activityId) {
            return $query->where('activity_id', $activityId);
        }])
            ->where('participant_id', $participantId)
            ->where('course_id', $courseId)
            ->first();


        if ($learn && $learn->learnItems->first()) {
            return [
                'score' => $this->getTotalScore(unserialize($learn->learnItems->first()->response)),
                'correct' => $this->getCorrectAnswer(unserialize($learn->learnItems->first()->response)),
                'wrong' => $this->getWrongAnswer(unserialize($learn->learnItems->first()->response))
            ];
        }
    }

    public function getTotalScore($response)
    {
        $a = 0;
        foreach ($response as $item) {
            $a = $a + $item['score'];
        }

        return $a;
    }

    public function getCorrectAnswer($response)
    {
        $a = search($response, 'is_correct', true);
        return count($a);
    }

    public function getWrongAnswer($response)
    {
        $a = search($response, 'is_correct', false);
        return count($a);
    }
}
