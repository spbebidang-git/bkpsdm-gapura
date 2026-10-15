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
        $restrictedTrainer = $user->hasRole('trainer')
            && $user->getRoleNames()->every(fn (string $role) => in_array($role, ['trainer', 'participant'], true));

        $courses = $restrictedTrainer
            ? $course->getByInstructor($user->id)
            : $course->getByAdmin();

        return Inertia::render('Instructor/Dashboard', [
            'courses' => $courses,
        ]);
    }
}
