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
        // Jika user login dan role-nya Instructor
        if ($request->user() && $request->user()->hasRole('Instructor')) {
            
            // Izinkan hanya route 'home' (halaman utama public)
            // Atau route yang memang milik instructor/auth/dashboard
            $allowedRoutesForInstructor = [
                'home',
                'dashboard',
                'profile.edit',
                'profile.update',
                'profile.destroy',
                'logout',
                // Semua route dengan prefix instructor.* sudah dihandle di group route tersendiri
            ];

            // Jika route name saat ini TIDAK ada di list allowed, DAN route-nya BUKAN route instructor
            if ($request->route() && !in_array($request->route()->getName(), $allowedRoutesForInstructor)) {
                
                // Pastikan route tidak nge-prefix instructor (contoh: instructor.dashboard)
                if (strpos($request->route()->getName(), 'instructor.') !== 0) {
                    
                    // Blokir akses dengan abort 403 atau redirect ke halaman yang diizinkan
                    return redirect()->route('home')->with('danger', 'Anda tidak memiliki akses ke halaman tersebut.');
                }
            }
        }

        return $next($request);
    }
}
