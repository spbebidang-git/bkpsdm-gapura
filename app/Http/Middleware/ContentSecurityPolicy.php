<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class ContentSecurityPolicy
{
    public function handle(Request $request, Closure $next): Response
    {
        Vite::useCspNonce();

        $response = $next($request);

        if (
            $response->headers->has('Content-Type') &&
            str_contains($response->headers->get('Content-Type', ''), 'text/html')
        ) {

            $nonce = Vite::cspNonce();

            $response->headers->set(
                'Content-Security-Policy',
                "script-src 'self' 'nonce-{$nonce}'; " .
                    "style-src 'self' 'unsafe-inline'; " .
                    "object-src 'none'; " .
                    "base-uri 'self';"
            );
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        return $response;
    }
}
