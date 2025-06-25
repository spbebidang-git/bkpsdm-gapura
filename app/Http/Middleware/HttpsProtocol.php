<?php

namespace App\Http\Middleware;

use Closure;

class HttpsProtocol
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (app()->environment('production')) {
            $host = $request->header('host');

            // Jika request pakai www, redirect ke tanpa www
            if (str_starts_with($host, 'www.')) {
                $nonWwwHost = substr($host, 4);
                $scheme = $request->secure() ? 'https://' : 'http://';
                $url = $scheme . $nonWwwHost . $request->getRequestUri();
                return redirect()->to($url, 301);
            }

            // Jika request belum pakai https, redirect ke https
            if (!$request->secure()) {
                $url = 'https://' . $host . $request->getRequestUri();
                return redirect()->to($url, 301);
            }
        }

        return $next($request);
    }
}
