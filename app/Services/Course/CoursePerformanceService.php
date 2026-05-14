<?php

namespace App\Services\Course;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class CoursePerformanceService
{
    protected function getPreTestNote($avg)
    {
        if ($avg <= 50) return 'Pada awal pelaksanaan pelatihan, rata - rata peserta kurang memahami materi.';
        if ($avg <= 70) return 'Pada awal pelaksanaan pelatihan, rata - rata peserta cukup memahami materi.';
        return 'Pada awal pelaksanaan pelatihan, rata - rata peserta sudah memahami materi.';
    }

    protected function getPostTestNote($avg)
    {
        if ($avg <= 50) return 'Setelah mengikuti pelatihan, rata - rata peserta masih kurang memahami materi.';
        if ($avg <= 70) return 'Setelah mengikuti pelatihan, rata - rata peserta cukup memahami materi.';
        return 'Setelah mengikuti pelatihan, rata - rata peserta sudah memahami materi.';
    }

    public function getPerformance()
    {
        return Cache::remember('admin.course_performance', 3600, function () {
            $learnData = DB::table('learns')
                ->join('learn_items', 'learn_items.learn_id', '=', 'learns.id')
            ->join('courses', 'courses.id', '=', 'learns.course_id')
            ->select(
                'learns.id as learn_id',
                'learns.course_id',
                'courses.title as course_title',
                'learn_items.type',
                'learn_items.response',
                'learns.finished_at'
            )
            ->whereIn('learn_items.type', ['pre-test', 'post-test'])
            ->whereNotNull('learns.finished_at')
            ->get()
            ->map(function ($item) {
                $response = @unserialize($item->response);
                $item->total_score = (is_array($response)) ? getTotalScore($response) : null;
                return $item;
            });

        $byLearn = $learnData->groupBy('learn_id')->map(function ($items) {
            $pre = $items->firstWhere('type', 'pre-test');
            $post = $items->firstWhere('type', 'post-test');
            $preScore = $pre?->total_score ?? 0;
            $postScore = $post?->total_score ?? 0;

            return [
                'course_id' => $items->first()->course_id,
                'course_title' => $items->first()->course_title,
                'pre_test' => $preScore,
                'post_test' => $postScore,
                'margin' => $postScore - $preScore,
                'finished_at' => $items->first()->finished_at,
            ];
        });

        $scheduleUsers = DB::table('schedule_user')
            ->join('schedules', 'schedules.id', '=', 'schedule_user.schedule_id')
            ->select('schedules.course_id', DB::raw('COUNT(DISTINCT schedule_user.participant_id) as total_peserta'))
            ->groupBy('schedules.course_id')
            ->get()
            ->keyBy('course_id');

        return $byLearn->groupBy('course_id')->map(function ($items, $courseId) use ($scheduleUsers) {
            $avgPre = round($items->avg('pre_test'), 2);
            $avgPost = round($items->avg('post_test'), 2);
            $avgMargin = round($avgPost - $avgPre, 2);

            $pesertaMengikuti = $scheduleUsers[$courseId]->total_peserta ?? 0;
            $pesertaSelesai = $items->whereNotNull('finished_at')->count();

            return [
                'course_id' => $courseId,
                'course_title' => $items->first()['course_title'],
                'avg_pre_test' => $avgPre,
                'avg_post_test' => $avgPost,
                'avg_margin' => $avgMargin,
                'pre_test_note' => $this->getPreTestNote($avgPre),
                'post_test_note' => $this->getPostTestNote($avgPost),
                'peserta_mengikuti' => $pesertaMengikuti,
                'peserta_selesai' => $pesertaSelesai,
            ];
        })->values();
        });
    }
}
