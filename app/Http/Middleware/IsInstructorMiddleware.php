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
        if ($user && ($user->hasRole('Instructor') || $user->hasRole('Admin'))) {
            $this->instructor = $user;
            view()->share('instructor', $this->instructor);

            return $next($request);
        }

        if ($user && $user->hasRole('Participant')) {
            return to_route('home');
        }

        return to_route('sign-in.index');
    }
}
