<?php

namespace App\Services\Learn;

use App\Models\Course\Activity;
use App\Models\Learn\Learn;
use App\Models\Learn\LearnItem;
use Carbon\Carbon;
use PDO;

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

    public function checkValidOpenActivity($participantId, $courseId, $activityId)
    {
        # get all activity 
        $activities = Activity::where('course_id', $courseId)->get();
        # get learn items 
        $learn = Learn::with('learnItems')->where('participant_id', $participantId)->where('course_id', $courseId)->first();


        $currentActivityId = $activityId; // Ganti dengan activity_id saat ini

        $sortedActivityIds = $activities->pluck('id')->sort()->values()->toArray(); // Mengambil daftar activity_id yang sudah diurutkan

        $learnedActivityIds = $learn->learnItems->pluck('activity_id')->toArray(); // Mengambil daftar activity_id yang sudah ada di learnItems



        // 1. Validasi jika `currentActivityId` adalah activity pertama
        if ($currentActivityId === $sortedActivityIds[0]) {
            return true; // Valid jika adalah yang pertama, baik ada atau tidak di learnItems
        }

        // 2. Validasi jika `currentActivityId` adalah activity kedua
        $index = array_search($currentActivityId, $sortedActivityIds);
        if ($index === 1) { // Activity kedua
            if (in_array($sortedActivityIds[0], $learnedActivityIds)) {
                return true; // Valid jika activity pertama ada di learnItems
            } else {
                return [1]; // Tidak valid jika activity pertama belum ada di learnItems
            }
        }

        // 3. Validasi jika `currentActivityId` lebih dari yang pertama
        if ($index > 1) {
            if (empty($learnedActivityIds)) {
                return [2]; // Tidak valid jika belum ada learnItems sama sekali
            }

            // Cek apakah semua activity sebelumnya sudah ada di learnItems
            $previousActivities = array_slice($sortedActivityIds, 0, $index);
            foreach ($previousActivities as $activityId) {
                if (!in_array($activityId, $learnedActivityIds)) {
                    return [3]; // Tidak valid jika ada activity sebelumnya yang belum dipelajari
                }
            }
            return true; // Valid jika semua activity sebelumnya sudah ada
        }

        return true; // Jika tidak memenuhi kondisi lain, maka tidak valid
    }

    public function isLearned($participantId, $courseId)
    {
        $learn = Learn::where('participant_id', $participantId)->where('course_id', $courseId)->first();

        if ($learn) {
            return true;
        }

        Learn::create([
            'participant_id' => $participantId,
            'course_id' => $courseId,
        ]);

        return false;
    }

    // public function checkValidOpenActivity()
    // {
    //     # get all activity 
    //     $activities = Activity::where('course_id', 1)->get();
    //     # get learn items 
    //     $learn = Learn::with('learnItems')->where('participant_id', 7)->where('course_id', 1)->first();

    //     $currentActivityId = 20; // Ganti dengan `activity_id` saat ini
    //     $sortedActivityIds = $activities->pluck('id')->sort()->values()->toArray(); // Mengambil semua `activity_id` secara berurutan
    //     $learnedActivityIds = $learn->learnItems->pluck('activity_id')->sort()->values()->toArray(); // Mengambil semua `activity_id` di `learnItems` secara berurutan

    //     // Jika tidak ada `learnItems`, validasi hanya untuk `activity_id` pertama
    //     if (empty($learnedActivityIds)) {
    //         return $currentActivityId === $sortedActivityIds[0] ? true : [];
    //     }

    //     // Dapatkan `activity_id` terbesar di `learnItems`
    //     $lastLearnedActivityId = end($learnedActivityIds);

    //     // Cari indeks dari `lastLearnedActivityId` di dalam daftar `activities`
    //     $lastIndex = array_search($lastLearnedActivityId, $sortedActivityIds);

    //     // Validasi `currentActivityId`
    //     if ($lastIndex !== false) {
    //         // Pastikan `currentActivityId` adalah elemen berikutnya dalam urutan
    //         if (isset($sortedActivityIds[$lastIndex + 1]) && $sortedActivityIds[$lastIndex + 1] === $currentActivityId) {
    //             return true;
    //         } else {
    //             return []; // Tidak valid jika `currentActivityId` bukan elemen berikutnya
    //         }
    //     }

    //     return []; // Tidak valid jika tidak memenuhi kondisi
    // }
}
