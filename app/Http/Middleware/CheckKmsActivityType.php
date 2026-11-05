<?php

namespace App\Http\Middleware;

use App\Models\Course\Course;
use Closure;
use Illuminate\Http\Request;

class CheckKmsActivityType
{
    public function handle(Request $request, Closure $next, string $type)
    {
        $courseParam = $request->route('course');
        $course = $courseParam instanceof Course
            ? $courseParam
            : Course::where('slug', $courseParam)->first();

        abort_unless(
            $course?->allowsActivityType($type),
            403,
            'KMS hanya mendukung kegiatan literatur dan video.'
        );

        return $next($request);
    }
}
