<?php

namespace App\Logging;

final class LogReader
{
    public const MAX_READ_BYTES = 512_000;

    public function __construct(private readonly Redactor $redactor) {}

    /** Decode before redacting so unknown secret values are hidden by their keys. */
    public function line(string $line): array
    {
        $decoded = json_decode($line, true, 32);
        if (is_array($decoded)) {
            $safe = $this->redactor->clean($decoded);
            $context = is_array($safe['context'] ?? null) ? $safe['context'] : [];
            $extra = is_array($safe['extra'] ?? null) ? $safe['extra'] : [];
            $scalar = fn ($value) => is_scalar($value) ? (string) $value : '';

            return [
                'timestamp' => $scalar($safe['datetime'] ?? $safe['timestamp'] ?? ''),
                'level' => strtolower($scalar($safe['level_name'] ?? 'unknown')),
                'message' => $scalar($safe['message'] ?? '[Format log tidak dikenal]'),
                'request_id' => $scalar($extra['request_id'] ?? $context['request_id'] ?? ''),
                'process_id' => $scalar($extra['process_id'] ?? $context['process_id'] ?? ''),
                'context' => $context + $extra,
                'legacy' => false,
                'text' => $this->encode($safe),
            ];
        }
        // A damaged JSON record cannot be safely redacted by key: fail closed.
        $text = preg_match('/^\s*(?:\{|\[(?!\d{4}-\d{2}-\d{2} ))/', $line) ? '[Malformed log record omitted]' : $this->redactor->text($line);
        preg_match('/^\[([^\]]+)\]\s+[^.\s]+\.([A-Z]+):\s*(.*)$/', $text, $parts);

        return ['timestamp' => $parts[1] ?? '', 'level' => strtolower($parts[2] ?? 'legacy'), 'message' => $parts[3] ?? $text,
            'request_id' => '', 'process_id' => '', 'context' => [], 'legacy' => true, 'text' => $text];
    }

    public function tail(?string $path, array $filters): array
    {
        $result = ['entries' => [], 'available' => false, 'truncated' => false, 'max_bytes' => self::MAX_READ_BYTES, 'max_entries' => 200];
        if ($path === null || ($handle = fopen($path, 'rb')) === false) {
            return $result;
        }
        try {
            $size = fstat($handle)['size'];
            $start = max(0, $size - self::MAX_READ_BYTES);
            // Check the preceding byte so a record on an exact boundary is retained.
            fseek($handle, max(0, $start - 1));
            $preceding = $start > 0 ? fread($handle, 1) : "\n";
            $raw = stream_get_contents($handle, min($size, self::MAX_READ_BYTES)) ?: '';
            if ($start > 0 && $preceding !== "\n") {
                $newline = strpos($raw, "\n");
                $raw = $newline === false ? '' : substr($raw, $newline + 1);
            }
        } finally {
            fclose($handle);
        }
        $entries = [];
        foreach (explode("\n", $raw) as $line) {
            if (trim($line) === '') {
                continue;
            }
            $entry = strlen($line) > 65536 ? $this->line('Oversized log record omitted') : $this->line($line);
            if (($filters['level'] ?? '') !== '' && $entry['level'] !== $filters['level']) {
                continue;
            }
            if (($filters['request_id'] ?? '') !== '' && $entry['request_id'] !== $filters['request_id']) {
                continue;
            }
            if (($filters['q'] ?? '') !== '' && ! str_contains(mb_strtolower($entry['text']), mb_strtolower($filters['q']))) {
                continue;
            }
            unset($entry['text']);
            $entries[] = $entry;
        }

        return array_replace($result, ['entries' => array_reverse(array_slice($entries, -200)), 'available' => true, 'truncated' => $start > 0 || count($entries) > 200]);
    }

    public function download(string $path): void
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            return;
        }
        try {
            $discard = false;
            while (($line = fgets($handle, 65537)) !== false) {
                $complete = str_ends_with($line, "\n") || feof($handle);
                if (! $complete || $discard) {
                    if (! $discard) {
                        echo "[Oversized log record omitted]\n";
                    }
                    $discard = ! $complete;

                    continue;
                }
                echo $this->line(rtrim($line, "\r\n"))['text']."\n";
            }
        } finally {
            fclose($handle);
        }
    }

    private function encode(array $value): string
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PARTIAL_OUTPUT_ON_ERROR);
        if (strlen($json) > SafeJsonFormatter::MAX_BYTES) {
            return '{"message":"Oversized log record omitted","context":{"truncated":true}}';
        }

        return $json;
    }
}
