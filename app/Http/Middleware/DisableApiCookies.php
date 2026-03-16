<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menghapus semua Set-Cookie header dari API responses.
 * Ini memastikan API client (IOP) tidak menerima cookies
 * yang tidak diperlukan dan bisa menyebabkan masalah.
 */
class DisableApiCookies
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->remove('Set-Cookie');

        return $response;
    }
}
