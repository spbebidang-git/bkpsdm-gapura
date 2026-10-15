<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsInstructorMiddleware
{
    protected $instructor;

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Admin absorbs instructor course management
        if ($user && $user->hasAnyRole(['super_admin', 'trainer', 'administrator'])) {
            $this->instructor = $user;
            view()->share('instructor', $this->instructor);

            return $next($request);
        }

        if ($user && $user->hasRole('participant')) {
            return to_route('participant.dashboard');
        }

        return to_route('sign-in.index');
    }
}
