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

        // Halaman pemutar H5P terisolasi: CSP dilonggarkan khusus karena
        // h5p-standalone butuh skrip/style inline. Halaman ini hanya memuat
        // paket H5P yang kita ekstrak sendiri, dan hanya boleh diframe oleh
        // origin sendiri.
        if ($request->is('h5p-view/*') || $request->is('scorm-view/*') || $request->is('scorm-content/*')) {
            $response->headers->set(
                'Content-Security-Policy',
                "default-src 'self'; " .
                    "script-src 'self' 'unsafe-inline' 'unsafe-eval' 'wasm-unsafe-eval'; " .
                    "style-src 'self' 'unsafe-inline'; " .
                    "img-src 'self' data: blob:; " .
                    "media-src 'self' blob: data:; " .
                    "font-src 'self' data:; " .
                    "connect-src 'self' blob: data:; " .
                    "frame-src 'self' blob: https://www.youtube.com; " .
                    "frame-ancestors 'self'; " .
                    "base-uri 'self'; " .
                    "object-src 'none';"
            );
            $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            return $response;
        }

        // Header keamanan umum
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        $response->headers->set('Referrer-Policy', 'same-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=()');

        // Content Security Policy – nonce-based untuk mencegah XSS
        // H5P/SCORM yang membutuhkan eval dibatasi pada route terisolasi di atas.
        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; " .
                "script-src 'self' 'nonce-{$nonce}' https://cdn.datatables.net https://code.jquery.com https://cdn.jsdelivr.net; " .
                "style-src 'self' https://cdn.datatables.net https://fonts.bunny.net https://fonts.googleapis.com 'unsafe-inline'; " .
                "font-src 'self' https://fonts.bunny.net https://fonts.gstatic.com; " .
                "img-src 'self' data: blob:; " .
                "connect-src 'self' https: ws: wss:; " .
                "frame-src 'self' https://www.youtube.com https://pdf-hosting.com; " .
                "frame-ancestors 'none'; " .
                "base-uri 'self'; " .
                "object-src 'none';"
        );

        return $response;
    }
}
