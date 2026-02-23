<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Course\Course;

class CheckCourseLock
{
    public function handle(Request $request, Closure $next)
    {
        $courseParam = $request->route('course');
        $course = null;

        if ($courseParam instanceof Course) {
            $course = $courseParam;
        } elseif (is_string($courseParam)) {
            $course = Course::where('slug', $courseParam)->first();
        }
        
        if ($course && $course->is_locked) {
            $routeName = $request->route()->getName();
            
            // Allow read-only routes like index and result to pass through
            if (in_array($routeName, [
                'instructor.course.activity.activity',
                'instructor.course.activity.result',
                'instructor.course.activity.variant.index',
            ])) {
                return $next($request);
            }

            return redirect()->route('instructor.course.show', $course->slug)
                             ->with('danger', 'Materi sedang dikunci, aktivitas tidak dapat diubah.');
        }

        return $next($request);
    }
}
