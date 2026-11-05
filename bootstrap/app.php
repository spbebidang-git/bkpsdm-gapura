<?php

// use App\Http\Middleware\isInstructorMiddleware;
use App\Http\Middleware\CheckCourseLock;
use App\Http\Middleware\CheckKmsActivityType;
use App\Http\Middleware\CheckParticipantEnrollmentAccess;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\HttpsProtocol;
use App\Http\Middleware\RequestLogContext;
use App\Http\Middleware\SecureFileUploadMiddleware;
use App\Http\Middleware\SecurityHeaders;
use App\Logging\LogContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(RequestLogContext::class);

        $trustedProxies = env('TRUSTED_PROXIES', '*');

        $middleware->trustProxies(
            at: $trustedProxies === '*'
                ? '*'
                : array_filter(array_map('trim', explode(',', $trustedProxies)))
        );

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            HttpsProtocol::class,
            // \App\Http\Middleware\SecureHeaders::class,
            SecureFileUploadMiddleware::class,
            SecurityHeaders::class,
        ]);

        // $middleware->append(isInstructorMiddleware::class);
        $middleware->use([
            // App\Http\Middleware\IsInstructorMiddleware::class
        ]);

        $middleware->alias([
            'abilities' => CheckAbilities::class,
            'course.lock' => CheckCourseLock::class,
            'course.activity' => CheckKmsActivityType::class,
            'participant.enrollment' => CheckParticipantEnrollmentAccess::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->dontReportDuplicates();
        $exceptions->context(fn (Throwable $exception) => app(LogContext::class)->forException($exception) + array_filter([
            'request_id' => request()->attributes->get('request_id'),
        ]));
        $exceptions->respond(function ($response) {
            if ($id = request()->attributes->get('request_id')) {
                $response->headers->set('X-Request-ID', $id);
            }

            return $response;
        });
    })->create();
