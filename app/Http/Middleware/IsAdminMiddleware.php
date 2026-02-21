<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAdminMiddleware
{
    protected $admin;

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->hasRole('Admin')) {
            view()->share('admin', $user);

            // Pastikan tidak loop
            if (!$request->routeIs('admin.*')) {
                return to_route('admin.dashboard');
            }

            return $next($request);
        }

        if ($user && $user->hasRole('Instructor')) {
            return to_route('instructor.dashboard');
        }

        if ($user && $user->hasRole('Participant')) {
            return to_route('home');
        }

        return to_route('sign-in.index');
    }
}
