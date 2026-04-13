<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        Vite::useCspNonce();

        $response = $next($request);

        $nonce = Vite::cspNonce();

        // Header keamanan umum
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        $response->headers->set('Referrer-Policy', 'same-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=()');

        // Content Security Policy – nonce-based untuk mencegah XSS
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; " .
                "script-src 'self' 'nonce-{$nonce}' https://cdn.datatables.net https://code.jquery.com https://cdn.jsdelivr.net 'unsafe-eval'; " .
                "style-src 'self' https://cdn.datatables.net https://fonts.bunny.net https://fonts.googleapis.com 'unsafe-inline'; " .
                "font-src 'self' https://fonts.bunny.net https://fonts.gstatic.com https:; " .
                "img-src 'self' data: blob:; " .
                "connect-src 'self' https: http: ws:; " .
                "frame-src 'self' https://www.youtube.com https://pdf-hosting.com; " .
                "frame-ancestors 'none'; " .
                "base-uri 'self'; " .
                "object-src 'none';"
        );

        return $response;
    }
}
