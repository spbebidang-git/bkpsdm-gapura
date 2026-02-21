<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsInstructorMiddleware
{
    protected $instructor;

    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->hasRole('Instructor')) {
            $this->instructor = $request->user();

            view()->share('instructor', $this->instructor);

            return $next($request);
        }

        if ($request->user() && $request->user()->hasRole('Admin')) {
            return to_route('admin.dashboard');
        }

        if ($request->user() && $request->user()->hasRole('Participant')) {
            return to_route('home');
        }

        return to_route('sign-in.index');
    }
}
