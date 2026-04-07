<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Di environment local, jangan set CSP agar dev lebih fleksibel
        if (app()->environment('local')) {
            return $response;
        }

        // Header keamanan umum
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=()');

        // Content Security Policy – allow DataTables & jQuery CDN
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; " .
                "script-src 'self' https://cdn.datatables.net https://code.jquery.com 'unsafe-inline' 'unsafe-eval'; " .
                "style-src 'self' https://cdn.datatables.net https://fonts.bunny.net 'unsafe-inline'; " .
                "font-src 'self' https://fonts.bunny.net https:; " .
                "img-src 'self' data: blob:; " .
                "connect-src 'self' https: http: ws:; " .
                "frame-src 'self' https://www.youtube.com https://pdf-hosting.com; " .
                "frame-ancestors 'none';"
        );



        return $response;
    }
}
