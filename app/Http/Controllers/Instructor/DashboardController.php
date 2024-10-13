<?php

namespace App\Http\Controllers\Instructor;

use App\Models\Course\Course;
use App\Services\Course\CourseService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController
{
    public function __invoke(Request $request)
    {
        $course = new CourseService();

        return Inertia::render('Instructor/Dashboard', [
            'courses' => $course->getByInstructor($request->user()->id)
        ]);
    }
}
