<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HttpsProtocol
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('production')) {
            $host = $request->header('host');
            $canonicalHost = str_starts_with($host, 'www.') ? substr($host, 4) : $host;

            if (!$request->secure() || $host !== $canonicalHost) {
                return redirect()->to('https://' . $canonicalHost . $request->getRequestUri(), 301);
            }
        }

        return $next($request);
    }
}
