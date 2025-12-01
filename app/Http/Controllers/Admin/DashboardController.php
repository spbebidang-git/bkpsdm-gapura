<?php

namespace App\Http\Controllers\Admin;

use App\Models\Course\Course;
use App\Models\Learn\Learn;
use App\Models\User;
use App\Services\Course\CoursePerformanceService;
use Illuminate\Http\Request;

class DashboardController
{
    public function __invoke(Request $request, CoursePerformanceService $service)
    {
        // sementara data dummy
        $totalCourses = Course::count();
        $totalParticipants = User::role('participant')->count();
        $totalFinished = Learn::whereNotNull('finished_at')->count();
        $totalInstructors = User::role('instructor')->count();

        // contoh data kursus terpopuler
        $popularCourses = Course::select('courses.id', 'courses.title')
            ->join('schedules', 'schedules.course_id', '=', 'courses.id')
            ->join('schedule_user', 'schedule_user.schedule_id', '=', 'schedules.id')
            ->leftJoin('learns', function ($join) {
                $join->on('learns.course_id', '=', 'courses.id')
                    ->whereNotNull('learns.finished_at');
            })
            ->groupBy('courses.id', 'courses.title')
            ->selectRaw('
        COUNT(DISTINCT schedule_user.participant_id) as participants,
        COUNT(DISTINCT learns.participant_id) as completion
    ')
            ->orderByDesc('participants')
            ->get()
            ->map(function ($course) {
                $participants = (int) $course->participants;
                $completion = (int) $course->completion;

                // Hindari pembagian nol
                $completionRate = $participants > 0
                    ? round(($completion / $participants) * 100, 2)
                    : 0;

                return [
                    'name' => $course->title,
                    'participants' => $participants,
                    'completion' => $completionRate, // sekarang ini persentase %
                    'badge' => 'bg-label-success',
                ];
            });

        $result = $service->getPerformance();

        return view('admin.dashboard.index', compact(
            'totalCourses',
            'totalParticipants',
            'totalFinished',
            'totalInstructors',
            'popularCourses',
            'result'
        ));
    }
}
