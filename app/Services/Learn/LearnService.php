<?php

namespace App\Services\Learn;

use App\Models\Course\Activity;
use App\Models\Learn\Learn;
use App\Models\Learn\LearnItem;
use Carbon\Carbon;

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

    public function resetLearning($participantId, $courseId, $expired = null)
    {
        $learn = Learn::with('learnItems')->where('participant_id', $participantId)
            ->where('course_id', $courseId)
            ->first();

        if ($learn && $expired) {
            // Menghitung waktu 100 jam dari created_at
            $threshold = $learn->created_at->addHours($expired);

            // Membandingkan dengan waktu sekarang
            if (Carbon::now()->greaterThan($threshold)) {
                // Lebih dari 100 jam
                $learn->delete();
            }
        }
    }

    public function resetFromPostTest($participantId, $courseId)
    {
        $learn = Learn::with('learnItems')->where('participant_id', $participantId)
            ->where('course_id', $courseId)
            ->first();


        if ($learn) {
            $learn->delete();
        }
    }

    public function checkValidNextActivity($activityId) {}

    public function checkTrial($participantId, $courseId, $activityId)
    {
        $learn = Learn::with(['learnItems' => function ($query) use ($activityId) {
            $query->where('activity_id', $activityId);
        }])
            ->where('course_id', $courseId)
            ->where('participant_id', $participantId)
            ->first();

        if (!$learn && $learn->learnItems->first()) {
            return false;
        }
        // dd($learn->learnItems);

        if ($learn->learnItems->first()->trial_total == 3) {
            return 3;
        }

        return $learn;
    }

    public function getInititalActivityId($courseId)
    {
        return Activity::where('course_id', $courseId)->first()->id;
    }

    public function setTrialTotal($learnItem)
    {
        $learnItem->trial_total = $learnItem->trial_total + 1;
        $learnItem->save();
    }
}
