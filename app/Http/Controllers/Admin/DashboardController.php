<?php

namespace App\Http\Controllers\Admin;

use App\Services\Course\CoursePerformanceService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController
{
    public function __invoke(Request $request, CoursePerformanceService $service)
    {
        $user = $request->user();
        $dashboard = $service->dashboard($user);

        $result = $service->getPerformance();

        return Inertia::render('Admin/Dashboard', [
            'totalCourses' => $dashboard['totalCourses'],
            'totalParticipants' => $dashboard['totalParticipants'],
            'totalFinished' => $dashboard['totalFinished'],
            'popularCourses' => $dashboard['popularCourses'],
            'result' => $result,
        ]);
    }
}
