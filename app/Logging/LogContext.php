<?php

namespace App\Logging;

use Illuminate\Support\Str;

/** Only safe correlation identifiers belong here; never requests or job payloads. */
final class LogContext
{
    private array $values = [];

    private array $scopes = [];

    private ?\WeakMap $exceptions = null;

    public function rememberException(\Throwable $exception): void
    {
        $this->exceptions ??= new \WeakMap;
        $this->exceptions[$exception] = $this->values;
    }

    public function forException(\Throwable $exception): array
    {
        return $this->exceptions[$exception] ?? [];
    }

    public function all(): array
    {
        return $this->values;
    }

    public function begin(string $scope, array $values): void
    {
        $this->scopes[] = [$scope, $this->values];
        $this->values = $values;
    }

    public function process(string $scope, array $values = [], bool $inherit = true): void
    {
        $parent = $inherit ? $this->values : [];
        $this->begin($scope, array_filter([
            'request_id' => $values['request_id'] ?? $parent['request_id'] ?? null,
            'parent_process_id' => $values['process_id'] ?? $parent['process_id'] ?? null,
            'process_id' => (string) Str::uuid(),
            'command' => $values['command'] ?? null,
            'job_id' => $values['job_id'] ?? null,
        ], fn ($value) => $value !== null));
    }

    public function end(string $scope): void
    {
        if (! in_array($scope, array_column($this->scopes, 0), true)) {
            return;
        }
        // Unwind child commands too when a command terminates by exception.
        while ($saved = array_pop($this->scopes)) {
            $this->values = $saved[1];
            if ($saved[0] === $scope) {
                return;
            }
        }
    }
}
