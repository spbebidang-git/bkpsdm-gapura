<?php

namespace App\Http\Controllers\Participant;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController
{
    public function __invoke(Request $request)
    {
        $user = request()->user();

        // total courses user ikut
        $totalCourses = \DB::table('schedule_user')
            ->where('participant_id', $user->id)
            ->count();

        // ambil semua data learns milik user
        $learns = \DB::table('learns')
            ->join('courses', 'courses.id', '=', 'learns.course_id')
            ->select('learns.*', 'courses.title')
            ->where('learns.participant_id', $user->id)
            ->get();

        // completed: finished_at != null
        $completed = $learns->whereNotNull('finished_at');
        $completedCourses = $completed->map(fn($item) => [
            'id' => $item->course_id,
            'title' => $item->title,
            'date' => optional($item->finished_at)->format('Y-m-d'),
        ])->values();

        // ongoing: finished_at == null
        $ongoing = $learns->whereNull('finished_at');
        $ongoingCourses = $ongoing->map(fn($item) => [
            'id' => $item->course_id,
            'title' => $item->title,
            'progress' => $item->progress ?? 0, // kalau ada field progress
        ])->values();

        // sertifikat: url_certificate != null
        $certificates = $learns->whereNotNull('url_certificate')->count();

        // avg progress
        // kalau tidak ada kolom progress, bisa dihitung dari rata-rata sub-item learn_items
        $avgProgress = $learns->avg('progress') ?? 0;

        return Inertia::render('Participant/Dashboard', [
            'stats' => [
                'totalCourses' => $totalCourses,
                'completedCourses' => $completed->count(),
                'ongoingCourses' => $ongoing->count(),
                'certificates' => $certificates,
                'avgProgress' => round($avgProgress),
            ],
            'ongoingCourses' => $ongoingCourses,
            'completedCourses' => $completedCourses,
        ]);
    }
}
