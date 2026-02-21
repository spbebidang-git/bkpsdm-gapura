<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsParticipantMiddleware
{
    protected $participant;

    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->hasRole('Participant')) {
            $this->participant = $request->user();
            view()->share('participant', $this->participant);
            return $next($request);
        }

        if ($request->user() && $request->user()->hasRole('Admin')) {
            return $this->redirectResponse($request, route('admin.dashboard'));
        }

        if ($request->user() && $request->user()->hasRole('Instructor')) {
            return $this->redirectResponse($request, route('instructor.dashboard'));
        }

        return $this->redirectResponse($request, route('sign-in.index'));
    }

    protected function redirectResponse(Request $request, $route)
    {
        if ($request->expectsJson() || $request->isMethod('POST')) {
            // Untuk AJAX/Inertia/Axios, kirim response custom
            return response()->json([
                'redirect' => $route
            ], 409);
        }

        return redirect($route);
    }
}
