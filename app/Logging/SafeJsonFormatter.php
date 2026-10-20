<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;

final class SafeJsonFormatter extends JsonFormatter
{
    public const MAX_BYTES = 48 * 1024;

    public function format(LogRecord $record): string
    {
        $redactor = app(Redactor::class);
        // Handler formatting happens after all processors, including interpolation/context.
        $record = $record->with(channel: mb_strcut($redactor->text($record->channel), 0, 128, 'UTF-8'), message: $redactor->text($record->message), context: $redactor->clean($record->context), extra: $redactor->clean($record->extra));
        $json = parent::format($record);
        if (strlen($json) > self::MAX_BYTES) {
            $json = parent::format($record->with(
                message: mb_strcut($record->message, 0, 4096, 'UTF-8'),
                context: $this->identifiers($record->context, ['event', 'request_id', 'process_id', 'exchange_id']) + ['truncated' => true],
                extra: $this->identifiers($record->extra, ['request_id', 'process_id', 'parent_process_id', 'command', 'job_id']),
            ));
        }

        return $json;
    }

    private function identifiers(array $values, array $keys): array
    {
        return array_map(fn ($value) => is_scalar($value) ? mb_strcut((string) $value, 0, 256, 'UTF-8') : '[OMITTED]', array_intersect_key($values, array_flip($keys)));
    }
}
