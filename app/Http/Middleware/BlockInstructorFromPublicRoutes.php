<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block Admin from public/participant pages.
 * Instructor module deferred — treat Instructor like non-public if still present.
 */
class BlockInstructorFromPublicRoutes
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->hasAnyRole(['super_admin', 'administrator']) && ! $user->hasRole('participant')) {
            $allowed = [
                'dashboard',
                'profile.edit',
                'profile.update',
                'profile.destroy',
                'logout',
            ];

            $name = $request->route()?->getName();
            if ($name && ! in_array($name, $allowed, true) && ! str_starts_with($name, 'admin.')) {
                return redirect()->route('admin.dashboard')
                    ->with('danger', 'Anda tidak memiliki akses ke halaman tersebut.');
            }
        }

        // Instructor still in DB: keep off public until module removed
        if ($user->hasRole('trainer') && ! $user->hasAnyRole(['super_admin', 'administrator', 'participant'])) {
            $name = $request->route()?->getName();
            if ($name && ! str_starts_with($name, 'instructor.') && $name !== 'logout') {
                return redirect()->route('admin.dashboard')
                    ->with('danger', 'Anda tidak memiliki akses ke halaman tersebut.');
            }
        }

        return $next($request);
    }
}
