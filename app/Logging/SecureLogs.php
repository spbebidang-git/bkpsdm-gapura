<?php

namespace App\Logging;

use Illuminate\Log\Logger;
use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\LogRecord;

final class SecureLogs
{
    public function __invoke(Logger $logger): void
    {
        $redactor = app(Redactor::class);
        $logger->pushProcessor(function (LogRecord $record) use ($redactor): LogRecord {
            return $record->with(
                message: $redactor->text($record->message),
                context: $redactor->clean($record->context),
                extra: $redactor->clean($record->extra),
            );
        });
        foreach ($logger->getHandlers() as $handler) {
            if ($handler instanceof FormattableHandlerInterface) {
                $handler->setFormatter(new JsonFormatter);
            }
        }
    }
}
