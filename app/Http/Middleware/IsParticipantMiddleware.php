<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsParticipantMiddleware
{
    protected $participant;

    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->is_participant) {
            $this->participant = $request->user();

            view()->share('participant', $this->participant);

            return $next($request);
        }

        if ($request->user() && $request->user()->is_admin) {
            return to_route('admin.dashboard');
        }

        if ($request->user() && $request->user()->is_instructor) {
            return to_route('instructor.dashboard');
        }

        return to_route('sign-in.participant.create');
    }
}
