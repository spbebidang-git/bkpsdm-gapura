<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $previousLocale = app()->getLocale();
        app()->setLocale('id');

        try {
            return $next($request);
        } finally {
            // Do not leak the admin locale to a subsequent request in long-lived workers.
            app()->setLocale($previousLocale);
        }
    }
}
