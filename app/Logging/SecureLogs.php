<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\LogRecord;

final class SecureLogs
{
    public function __invoke(Logger $logger): void
    {
        $redactor = app(Redactor::class);
        $logger->pushProcessor(function (LogRecord $record) use ($redactor): LogRecord {
            $logContext = app(LogContext::class);
            $context = $record->context;
            $exceptionIds = ($context['exception'] ?? null) instanceof \Throwable
                ? $logContext->forException($context['exception']) : [];
            if (isset($context['exception'])) {
                $exceptionIds = array_intersect_key($context, array_flip(['request_id', 'process_id', 'parent_process_id', 'job_id'])) + $exceptionIds;
            }
            $context = array_replace($context, $exceptionIds);

            return $record->with(
                message: $redactor->text($record->message),
                context: $redactor->clean($context + ['event' => isset($context['exception']) ? 'application.exception' : 'application.'.strtolower($record->level->name)]),
                extra: $redactor->clean(array_replace($record->extra, $logContext->all(), $exceptionIds)),
            );
        });
        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof FormattableHandlerInterface) {
                $handler->setFormatter(new SafeJsonFormatter);
            }
        }
    }
}
