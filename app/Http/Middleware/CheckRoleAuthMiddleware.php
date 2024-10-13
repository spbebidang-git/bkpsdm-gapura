<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRoleAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // if ($request->user()) {
        //     // dd($request->user()->is_admin);
        //     if ($request->user()->is_admin) {
        //         return redirect(route('admin.dashboard'));
        //     }

        //     if ($request->user()->is_instructor) {

        //         return to_route('instructor.dashboard');
        //     }

        //     if ($request->user()->is_participant) {
        //         return to_route('home');
        //     }
        // }

        return $next($request);
    }
}
