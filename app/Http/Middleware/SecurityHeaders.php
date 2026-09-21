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

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }
        $response->headers->set('Referrer-Policy', 'same-origin');
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=()');

        // Vite HMR only on local (npm run dev → port dinamis dari hot file)
        $vite = '';
        if (app()->environment('local')) {
            $hot = trim((string) @file_get_contents(public_path('hot')));
            if ($hot) {
                $host = str_replace(['http://', 'https://'], '', $hot);
                $vite = " http://{$host} ws://{$host}";
            } else {
                $vite = ' http://127.0.0.1:5173 http://localhost:5173 ws://127.0.0.1:5173 ws://localhost:5173';
            }
        }

        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; " .
                "script-src 'self' 'nonce-{$nonce}' https://cdn.datatables.net https://code.jquery.com https://cdn.jsdelivr.net 'unsafe-eval' 'wasm-unsafe-eval'{$vite}; " .
                "style-src 'self' https://cdn.datatables.net https://fonts.bunny.net https://fonts.googleapis.com 'unsafe-inline'{$vite}; " .
                "font-src 'self' https://fonts.bunny.net https://fonts.gstatic.com data:{$vite}; " .
                "img-src 'self' data: blob:; " .
                "connect-src 'self' https: ws: wss:{$vite}; " .
                "frame-src 'self' https://www.youtube.com https://pdf-hosting.com; " .
                "frame-ancestors 'none'; " .
                "base-uri 'self'; " .
                "object-src 'none';"
        );

        return $response;
    }
}
