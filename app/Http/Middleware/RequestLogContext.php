<?php

namespace App\Http\Middleware;

use App\Logging\LogContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class RequestLogContext
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = (string) Str::uuid();
        $request->attributes->set('request_id', $id);
        $context = app(LogContext::class);
        $context->begin('request', ['request_id' => $id]);
        try {
            $response = $next($request);
            $response->headers->set('X-Request-ID', $id);

            return $response;
        } finally {
            $context->end('request');
        }
    }
}
