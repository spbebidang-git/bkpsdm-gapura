<?php

namespace App\Services\Learning;

use App\Repositories\Course\Activity\Assessment\Contracts\QuestionRepositoryInterface;
use App\Repositories\Course\Activity\Contracts\ActivityRepositoryInterface;
use App\Repositories\Learning\Contracts\LearnItemRepositoryInterface;
use App\Repositories\Learning\Contracts\LearnRepositoryInterface;
use App\Repositories\Reporting\Contracts\LearningReportRepositoryInterface;
use Carbon\Carbon;

class LearnService
{
    public function __construct(
        private readonly LearnRepositoryInterface $learns,
        private readonly LearnItemRepositoryInterface $learnItems,
        private readonly ActivityRepositoryInterface $activities,
        private readonly QuestionRepositoryInterface $questions,
        private readonly LearningReportRepositoryInterface $reports,
    ) {}

    public function createLearn($participantId, $courseId, $activityId, array $learnItems, $scheduleId = null)
    {
        $learn = $this->learns->findByParticipantAndCourse($participantId, $courseId);

        if (! $learn) {
            $learn = $this->learns->create([
                'participant_id' => $participantId,
                'course_id' => $courseId,
            ]);
        }

        $this->createLearnItem($learn->id, $activityId, $learnItems);
    }

    public function createLearnItem($learnId, $activityId, $learnItems)
    {
        return $this->learnItems->updateOrCreateForLearnAndActivity(
            $learnId,
            $activityId,
            [
                'title' => 'title',
                'type' => $learnItems['type'],
                'response' => $learnItems['response'] ? serialize($learnItems['response']) : null,
                'status' => $learnItems['status'],
            ],
        );
    }

    public function getResultQuestion($participantId, $courseId, $activityId)
    {
        $learn = $this->learns->findByParticipantAndCourse($participantId, $courseId, [
            'learnItems' => fn ($query) => $query->where('activity_id', $activityId),
        ]);

        if (! $learn) {
            return [
                'score' => 0,
                'correct' => 0,
                'wrong' => 0,
            ];
        }

        return [
            'score' => $learn->learnItems->first() ? $this->getTotalScore(unserialize($learn->learnItems->first()->response)) : 0,
            'correct' => $learn->learnItems->first() ? $this->getCorrectAnswer(unserialize($learn->learnItems->first()->response)) : 0,
            'wrong' => $learn->learnItems->first() ? $this->getWrongAnswer(unserialize($learn->learnItems->first()->response)) : 0,
        ];
    }

    public function isPassedMinimumScore($participantId, $courseId, $activityId)
    {
        $result = $this->getResultQuestion($participantId, $courseId, $activityId);

        // Ambil min_score dari question (asumsi 1 soal mewakili activity)
        $question = $this->questions->findByActivity($activityId);

        $minScore = $question->min_score ?? 70; // default 70 jika tidak ditemukan

        return $result && isset($result['score']) && $result['score'] >= $minScore;
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
        $learn = $this->learns->findByParticipantAndCourse($participantId, $courseId, ['learnItems']);

        if ($learn && $expired) {
            // Menghitung waktu 100 jam dari created_at
            $threshold = $learn->created_at->addHours($expired);

            // Membandingkan dengan waktu sekarang
            if (Carbon::now()->greaterThan($threshold)) {
                // Lebih dari 100 jam
                $this->learns->delete($learn);
            }
        }
    }

    public function resetFromPostTest($participantId, $courseId)
    {
        $learn = $this->learns->findByParticipantAndCourse($participantId, $courseId, ['learnItems']);

        if ($learn) {
            $this->learns->delete($learn);
        }
    }

    public function checkValidNextActivity($activityId) {}

    public function checkTrial($participantId, $courseId, $activityId)
    {
        $learn = $this->learns->findByParticipantAndCourse($participantId, $courseId, [
            'learnItems' => fn ($query) => $query->where('activity_id', $activityId),
        ]);

        if (! $learn || ! $learn->learnItems->first()) {
            return false;
        }

        if ($learn->learnItems->first()->trial_total == 2) {
            return 2;
        }

        return $learn;
    }

    public function getInititalActivityId($courseId)
    {
        return $this->activities->getByCourse($courseId)->first()?->id;
    }

    public function setTrialTotal($learnItem)
    {
        $this->learnItems->update($learnItem, [
            'trial_total' => $learnItem->trial_total + 1,
        ]);
    }

    public function checkValidOpenActivity($participantId, $courseId, $activityId)
    {
        // get all activity
        $activities = $this->activities->getByCourse($courseId);
        // get learn items
        $learn = $this->learns->findByParticipantAndCourse($participantId, $courseId, ['learnItems']);

        $currentActivityId = $activityId; // Ganti dengan activity_id saat ini

        $sortedActivityIds = $activities->pluck('id')->sort()->values()->toArray(); // Mengambil daftar activity_id yang sudah diurutkan

        if (! $learn) {
            // If no learning record, only allow the first activity
            if (isset($sortedActivityIds[0]) && $currentActivityId === $sortedActivityIds[0]) {
                return true;
            }

            return [2];
        }

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
                if (! in_array($activityId, $learnedActivityIds)) {
                    return [3]; // Tidak valid jika ada activity sebelumnya yang belum dipelajari
                }
            }

            return true; // Valid jika semua activity sebelumnya sudah ada
        }

        return true; // Jika tidak memenuhi kondisi lain, maka tidak valid
    }

    public function isLearned($participantId, $courseId)
    {
        $learn = $this->learns->findByParticipantAndCourse($participantId, $courseId);

        if ($learn) {
            return true;
        }

        $this->learns->create([
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

    public function findLearn($courseId, $participantId)
    {
        return $this->learns->findByParticipantAndCourse($participantId, $courseId);
    }

    public function getFinishedAt($courseId, $participantId)
    {
        $learn = $this->findLearn($courseId, $participantId);

        if (! $learn || ! $learn->finished_at) {
            return null;
        }

        return Carbon::parse($learn->finished_at)->format('Y-m-d H:i');
    }

    public function getPostTestAvg($courseId, $participantId)
    {
        $data = $this->reports->getPostTestResponses($courseId, $participantId);

        $scores = [];

        foreach ($data as $row) {
            if (in_array($row->activity_type, ['pre-test', 'post-test'])) {
                try {
                    $response = @unserialize($row->response);
                    if (is_array($response)) {
                        foreach ($response as $item) {
                            if (isset($item['score'])) {
                                $scores[] = $item['score'];
                            }
                        }
                    }
                } catch (\Exception $e) {
                    // optional: log error
                }
            }
        }

        return count($scores) > 0
            ? round(array_sum($scores) / count($scores), 2)
            : 0;
    }
}
