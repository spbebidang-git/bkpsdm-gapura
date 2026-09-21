<?php

namespace App\Http\Middleware;

use App\Models\Learning\Learn;
use Closure;
use Illuminate\Http\Request;

class IsFinishedMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $learn = Learn::where('participant_id', $request->user()->id)->where('course_id', $request->course->id)->first();
        if ($learn->isAllowRating()) {
            return $next($request);
        }

        return to_route('participant.course.index');
    }
}
