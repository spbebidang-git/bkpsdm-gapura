<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsParticipantMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->hasRole('participant')) {
            view()->share('participant', $request->user());

            return $next($request);
        }

        if ($request->user() && $request->user()->hasAnyRole(['super_admin', 'administrator', 'trainer'])) {
            return $this->redirectResponse($request, route('admin.dashboard'));
        }

        return $this->redirectResponse($request, route('sign-in.index'));
    }

    protected function redirectResponse(Request $request, $route)
    {
        if ($request->expectsJson() || $request->isMethod('POST')) {
            return response()->json([
                'redirect' => $route,
            ], 409);
        }

        return redirect($route);
    }
}
