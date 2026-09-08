<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockInstructorFromPublicRoutes
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user) {
            // Evaluasi Admin
            if ($user->hasRole('Admin')) {
                $allowedRoutesForAdmin = [
                    'dashboard',
                    'profile.edit',
                    'profile.update',
                    'profile.destroy',
                    'logout',
                ];

                if ($request->route() && !in_array($request->route()->getName(), $allowedRoutesForAdmin)) {
                    if (strpos($request->route()->getName(), 'admin.') !== 0) {
                        return redirect()->route('admin.dashboard')->with('danger', 'Anda tidak memiliki akses ke halaman tersebut.');
                    }
                }
            }
        }

        return $next($request);
    }
}
