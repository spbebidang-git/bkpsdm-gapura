<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AuditAdminActions
{
    public function handle(Request $request, Closure $next): Response
    {
        $id = $request->attributes->get('request_id');
        $status = 500;
        $redirect = [];
        try {
            $response = $next($request);
            $status = $response->getStatusCode();
            $response->headers->set('X-Request-ID', $id);
            if ($request->routeIs('admin.course.activity.literature.store', 'admin.course.activity.literature.update') && $response->isRedirection()) {
                $redirect = [
                    'redirect_path' => parse_url((string) $response->headers->get('Location'), PHP_URL_PATH),
                    'flash_keys' => array_values(array_filter(['success', 'danger'], fn ($key) => $request->session()->has($key))),
                ];
            }

            return $response;
        } catch (\Throwable $e) {
            $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
            throw $e;
        } finally {
            if (! $request->isMethodSafe() || $status >= 400) {
                Log::channel('audit')->info('Permintaan admin diproses.', [
                    'event' => 'admin.request',
                    'actor_id' => $request->user()?->getAuthIdentifier(),
                    'route' => $request->route()?->getName(), 'method' => $request->method(),
                    'status' => $status, 'request_id' => $id,
                ] + $redirect);
            }
        }
    }
}
