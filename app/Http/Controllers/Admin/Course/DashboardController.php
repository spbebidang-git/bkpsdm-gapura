<?php

namespace App\Http\Controllers\Admin\Course;

use App\Models\Course\Course;
use App\Services\Course\CourseService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController
{
    public function __invoke(Request $request, CourseService $course)
    {
        $user = $request->user();

        // Admin sees all courses; Instructor only own
        $courses = $user->hasRole('Admin')
            ? $course->getByAdmin()
            : $course->getByInstructor($user->id);

        return Inertia::render('Instructor/Dashboard', [
            'courses' => $courses,
        ]);
    }
}
