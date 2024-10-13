<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAdminMiddleware
{
    protected $admin;

    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->is_admin) {
            $this->admin = $request->user();

            view()->share('admin', $this->admin);

            return $next($request);
        }

        if ($request->user() && $request->user()->is_instructor) {
            return to_route('instructor.dashboard');
        }

        if ($request->user() && $request->user()->is_participant) {
            return to_route('home');
        }

        return to_route('sign-in.admin.create');
    }
}
