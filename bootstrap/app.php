<?php

// use App\Http\Middleware\isInstructorMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $trustedProxies = env('TRUSTED_PROXIES', '*');

        $middleware->trustProxies(
            at: $trustedProxies === '*'
                ? '*'
                : array_filter(array_map('trim', explode(',', $trustedProxies)))
        );

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\HttpsProtocol::class,
            // \App\Http\Middleware\SecureHeaders::class,
            \App\Http\Middleware\SecureFileUploadMiddleware::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        // $middleware->append(isInstructorMiddleware::class);
        $middleware->use([
            // App\Http\Middleware\IsInstructorMiddleware::class
        ]);

        $middleware->alias([
            'course.lock' => \App\Http\Middleware\CheckCourseLock::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
