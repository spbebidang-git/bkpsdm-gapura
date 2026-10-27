<?php

namespace App\Services\Learn;

use App\Models\Course\Activity;
use App\Models\Course\Question;
use App\Models\Learn\Learn;
use App\Models\Learn\LearnItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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
        $learn = Learn::with(['learnItems' => function ($query) use ($activityId) {
            return $query->where('activity_id', $activityId);
        }])
            ->where('participant_id', $participantId)
            ->where('course_id', $courseId)
            ->first();

        if (!$learn) {
            return [
                'score' => 0,
                'correct' => 0,
                'wrong' => 0
            ];
        }

        $item = $learn->learnItems->first();

        // Baris tanpa jawaban tersimpan (response NULL) menghasilkan false saat
        // di-unserialize; diperlakukan sebagai belum menjawab, bukan error.
        $response = $item && $item->response ? unserialize($item->response) : false;

        if (!is_array($response)) {
            return [
                'score' => 0,
                'correct' => 0,
                'wrong' => 0
            ];
        }

        return [
            'score' => $this->getTotalScore($response),
            'correct' => $this->getCorrectAnswer($response),
            'wrong' => $this->getWrongAnswer($response)
        ];
    }

    public function isPassedMinimumScore($participantId, $courseId, $activityId)
    {
        $result = $this->getResultQuestion($participantId, $courseId, $activityId);

        // Ambil min_score dari question (asumsi 1 soal mewakili activity)
        $question = Question::where('activity_id', $activityId)->first();

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

        if (!$learn || !$learn->learnItems->first()) {
            return false;
        }

        if ($learn->learnItems->first()->trial_total == 2) {
            return 2;
        }

        return $learn;
    }

    public function getInititalActivityId($courseId)
    {
        return Activity::where('course_id', $courseId)
            ->orderBy('position')
            ->orderBy('id')
            ->value('id');
    }

    /**
     * Kegiatan tempat peserta harus melanjutkan ketika membuka kelas lagi.
     *
     * Mengikuti aturan gerbang di checkValidOpenActivity(): sebuah kegiatan
     * baru boleh dibuka bila semua kegiatan sebelumnya sudah tercatat di
     * learn_items. Jadi titik lanjut = kegiatan pertama yang belum tercatat.
     * Bila semuanya sudah tersentuh, arahkan ke yang belum berstatus `done`,
     * dan bila semua sudah selesai, ke kegiatan terakhir.
     */
    public function getResumeActivityId($participantId, $courseId)
    {
        $activityIds = Activity::where('course_id', $courseId)
            ->orderBy('position')
            ->orderBy('id')
            ->pluck('id');

        if ($activityIds->isEmpty()) {
            return 0;
        }

        $learn = Learn::with('learnItems')
            ->where('participant_id', $participantId)
            ->where('course_id', $courseId)
            ->first();

        if (!$learn) {
            return $activityIds->first();
        }

        $items = $learn->learnItems->keyBy('activity_id');

        foreach ($activityIds as $id) {
            if (!$items->has($id)) {
                return $id;
            }
        }

        foreach ($activityIds as $id) {
            if ($items->get($id)->status !== 'done') {
                return $id;
            }
        }

        return $activityIds->last();
    }

    public function setTrialTotal($learnItem)
    {
        $learnItem->trial_total = $learnItem->trial_total + 1;
        $learnItem->save();
    }

    public function checkValidOpenActivity($participantId, $courseId, $activityId)
    {
        # get all activity 
        $activities = Activity::where('course_id', $courseId)
            ->orderBy('position')
            ->orderBy('id')
            ->get();
        # get learn items 
        $learn = Learn::with('learnItems')->where('participant_id', $participantId)->where('course_id', $courseId)->first();


        $currentActivityId = $activityId; // Ganti dengan activity_id saat ini

        // Urutan kegiatan mengikuti kolom position. Aktivitas hasil impor H5P/
        // SCORM dapat memiliki ID lebih besar walaupun berada di tengah agenda.
        $sortedActivityIds = $activities->pluck('id')->values()->toArray();

        if (!$learn) {
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

    public function findLearn($courseId, $participantId)
    {
        return Learn::where('course_id', $courseId)
            ->where('participant_id', $participantId)
            ->first();
    }

    public function getFinishedAt($courseId, $participantId)
    {
        $learn = $this->findLearn($courseId, $participantId);

        if (!$learn || !$learn->finished_at) {
            return null;
        }

        return Carbon::parse($learn->finished_at)->format('Y-m-d H:i');
    }

    public function getPostTestAvg($courseId, $participantId)
    {
        $data = DB::table('learn_items')
            ->join('learns', 'learn_items.learn_id', '=', 'learns.id')
            ->join('activities', function ($join) {
                $join->on('activities.course_id', '=', 'learns.course_id')
                    ->on('activities.id', '=', 'learn_items.activity_id');
            })
            ->where('learns.course_id', $courseId)
            ->where('learns.participant_id', $participantId)
            ->whereNotNull('learns.finished_at')
            ->select('activities.type as activity_type', 'learn_items.response')
            ->get();

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
