<?php

namespace App\Providers;

use App\Logging\LogContext;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

final class LoggingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LogContext::class);
    }

    public function boot(): void
    {
        Event::listen(CommandStarting::class, fn ($event) => app(LogContext::class)->process('command:'.spl_object_id($event->input), ['command' => $event->command]));
        Event::listen(CommandFinished::class, fn ($event) => app(LogContext::class)->end('command:'.spl_object_id($event->input)));
        Queue::createPayloadUsing(fn () => ['gapura_log_context' => array_intersect_key(app(LogContext::class)->all(), array_flip(['request_id', 'process_id']))]);
        Event::listen(JobProcessing::class, function ($event) {
            $values = $event->job->payload()['gapura_log_context'] ?? [];
            app(LogContext::class)->process('job', array_intersect_key((array) $values, array_flip(['request_id', 'process_id'])) + ['job_id' => $event->job->getJobId()], false);
        });
        Event::listen(JobProcessed::class, fn () => app(LogContext::class)->end('job'));
        Event::listen(JobExceptionOccurred::class, function ($event) {
            // Laravel reports worker exceptions after JobExceptionOccurred. Keep only
            // their safe IDs until the exception object is garbage-collected.
            $context = app(LogContext::class);
            $context->rememberException($event->exception);
            $context->end('job');
        });
    }
}
