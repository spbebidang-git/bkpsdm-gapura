<?php

namespace App\Http\Controllers\Participant;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController
{
    public function __invoke(Request $request)
    {
        $user = request()->user();

        // Ambil semua data learns milik user (single source of truth)
        $learns = DB::table('learns')
            ->join('courses', 'courses.id', '=', 'learns.course_id')
            ->select('learns.*', 'courses.title', 'courses.slug')
            ->where('learns.participant_id', $user->id)
            ->get();

        // completed: finished_at != null
        $completed = $learns->filter(fn($item) => !is_null($item->finished_at));
        $completedCourses = $completed->map(fn($item) => [
            'id'    => $item->course_id,
            'title' => $item->title,
            'date'  => $item->finished_at ? Carbon::parse($item->finished_at)->format('d M Y') : '-',
        ])->values();

        // ongoing: finished_at == null
        $ongoing = $learns->filter(fn($item) => is_null($item->finished_at));

        // Hitung progress dinamis dari learn_items untuk setiap learn
        $ongoingCourses = $ongoing->map(function ($item) {
            // Hitung jumlah aktivitas yang sudah done di learn_items
            $doneCount = DB::table('learn_items')
                ->where('learn_id', $item->id)
                ->where('status', 'done')
                ->count();

            // Hitung total aktivitas di course
            $totalCount = DB::table('activities')
                ->where('course_id', $item->course_id)
                ->count();
            $progress = ($totalCount > 0) ? round(($doneCount / $totalCount) * 100) : 0;

            return [
                'id'       => $item->course_id,
                'title'    => $item->title,
                'progress' => $progress,
            ];
        })->values();

        // total courses = jumlah learns unik
        $totalCourses = $learns->unique('course_id')->count();

        // Sertifikat: url_certificate ada dan bukan '-'
        $certificates = $learns->filter(
            fn($item) =>
            !empty($item->url_certificate) && $item->url_certificate !== '-'
        )->count();

        // Avg progress: gabungkan dari ongoing (computed) dan completed (100%)
        $progressValues = array_merge(
            $ongoingCourses->pluck('progress')->toArray(),
            array_fill(0, $completed->count(), 100)
        );

        $avgProgress = count($progressValues) > 0
            ? round(array_sum($progressValues) / count($progressValues))
            : 0;

        return Inertia::render('Participant/Dashboard', [
            'stats' => [
                'totalCourses'    => $totalCourses,
                'completedCourses' => $completed->count(),
                'ongoingCourses'  => $ongoing->count(),
                'certificates'    => $certificates,
                'avgProgress'     => $avgProgress,
            ],
            'ongoingCourses'   => $ongoingCourses,
            'completedCourses' => $completedCourses,
        ]);
    }
}
