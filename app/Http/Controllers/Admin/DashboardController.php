<?php

namespace App\Http\Controllers\Admin;

use App\Models\Course\Course;
use App\Models\Learning\Learn;
use App\Models\Identity\User;
use App\Services\Course\CoursePerformanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class DashboardController
{
    public function __invoke(Request $request, CoursePerformanceService $service)
    {
        $user = $request->user();
        $courseScope = Course::query();
        if ($user?->hasRole('trainer')) {
            $courseScope->whereHas('trainers', fn ($query) => $query->where('user_id', $user->id));
        }
        $totalCourses = $courseScope->count();
        $totalParticipants = User::role('participant')->count();
        $totalFinished = Learn::whereNotNull('finished_at')->count();

        $popularCourses = Cache::remember('admin.dashboard.popularCourses.'.($user?->id ?? 'all'), 3600, function () use ($user) {
            $query = Course::select('courses.id', 'courses.title')
                ->join('enrollments', function ($join) {
                    $join->on('enrollments.course_id', '=', 'courses.id')->whereNull('enrollments.deleted_at');
                })
                ->leftJoin('learns', function ($join) {
                    $join->on('learns.enrollment_id', '=', 'enrollments.id')
                        ->whereNotNull('learns.finished_at')->whereNull('learns.deleted_at');
                })
                ->groupBy('courses.id', 'courses.title')
                ->selectRaw('
                    COUNT(DISTINCT enrollments.participant_id) as participants,
                    COUNT(DISTINCT learns.participant_id) as completion
                ')
                ->orderByDesc('participants')
                ->limit(10);

            if ($user?->hasRole('trainer')) {
                $query->whereExists(function ($subquery) use ($user) {
                    $subquery->selectRaw('1')->from('course_trainer')
                        ->join('trainers', 'trainers.id', '=', 'course_trainer.trainer_id')
                        ->whereColumn('course_trainer.course_id', 'courses.id')
                        ->where('trainers.user_id', $user->id);
                });
            }

            return $query->get()
                ->map(function ($course) {
                    $participants = (int) $course->participants;
                    $completion = (int) $course->completion;
                    $completionRate = $participants > 0
                        ? round(($completion / $participants) * 100, 2)
                        : 0;

                    return [
                        'name' => $course->title,
                        'participants' => $participants,
                        'completion' => $completionRate,
                    ];
                });
        });

        $result = $service->getPerformance();

        return Inertia::render('Admin/Dashboard', [
            'totalCourses' => $totalCourses,
            'totalParticipants' => $totalParticipants,
            'totalFinished' => $totalFinished,
            'popularCourses' => $popularCourses,
            'result' => $result,
        ]);
    }
}
