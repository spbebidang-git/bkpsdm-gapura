<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsInstructorMiddleware
{
    protected $instructor;

    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->is_instructor) {
            $this->instructor = $request->user();

            view()->share('instructor', $this->instructor);

            return $next($request);
        }

        if ($request->user() && $request->user()->is_admin) {
            return to_route('admin.dashboard');
        }

        if ($request->user() && $request->user()->is_participant) {
            return to_route('home');
        }

        return to_route('sign-in.index');
    }
}
