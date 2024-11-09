<?php

namespace App\Http\Middleware;

use App\Models\Learn\Learn;
use Closure;
use Illuminate\Http\Request;

class IsFinishedMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $learn = Learn::where('participant_id', $request->user()->id)->where('course_id', $request->course->id)->first();
        if ($learn->finished_at) {
            return $next($request);
        }

        return to_route('participant.course.index');
    }
}
