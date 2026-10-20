<?php

namespace App\Logging;

final class Redactor
{
    public function clean(mixed $value, int $depth = 0, ?int &$budget = null): mixed
    {
        $budget ??= 1000;
        if (--$budget < 0) {
            return '[ELEMENT LIMIT]';
        }
        if ($depth > 12) {
            return '[DEPTH LIMIT]';
        }
        if ($value instanceof \Throwable) {
            return $this->exception($value);
        }
        if (is_object($value)) {
            // Never serialize arbitrary models, requests or exception arguments.
            return '[OBJECT '.$value::class.']';
        }
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                if (count($result) >= 100 || $budget <= 0) {
                    $result['_truncated'] = true;
                    break;
                }
                $safeKey = is_string($key) ? $this->text($key) : $key;
                $result[$safeKey] = preg_match('/password|passwd|secret|token|authorization|cookie|api.?key|credential|\bnip\b|nip_|_nip|peserta_sample|email|phone|address|payload|response|body|headers|bindings|session|csrf|xsrf|parameters|raw_content|trace|peserta|full.?name|nama|alamat|telepon|mobile|private.?key|passphrase|(?:^|[_-])pass(?:$|[_-])|(?:^|[_-])nik(?:$|[_-])|ktp|identity.?number/i', (string) $key)
                    ? '[REDACTED]' : $this->clean($item, $depth + 1, $budget);
            }

            return $result;
        }

        return is_resource($value) ? '[RESOURCE]' : (is_string($value) ? $this->text($value) : $value);
    }

    public function exception(\Throwable $exception): array
    {
        $location = fn (string $file) => str_starts_with($file, base_path().DIRECTORY_SEPARATOR)
            ? substr($file, strlen(base_path()) + 1) : basename($file);

        return [
            'class' => $exception::class,
            'message' => $this->text($exception->getMessage()),
            'code' => $exception->getCode(),
            'file' => $location($exception->getFile()),
            'line' => $exception->getLine(),
            'frames' => array_map(fn ($frame) => [
                'file' => $location($frame['file'] ?? ''),
                'line' => $frame['line'] ?? null,
                'call' => ($frame['class'] ?? '').($frame['type'] ?? '').($frame['function'] ?? ''),
            ], array_slice($exception->getTrace(), 0, 20)),
        ];
    }

    public function text(string $text): string
    {
        if (strlen($text) > 65536) {
            return '[OVERSIZED TEXT OMITTED]';
        }
        foreach (['app.key', 'database.connections.mysql.password', 'services.sso.client_secret', 'services.sso.admin_pass', 'services.sso.api_key', 'filesystems.disks.backup_s3.secret', 'filesystems.disks.s3.secret', 'services.kedaton.client_secret', 'services.kedaton.data_key'] as $key) {
            $secret = config($key);
            if (is_string($secret) && strlen($secret) >= 6) {
                $text = str_replace($secret, '[REDACTED]', $text);
            }
        }
        $text = preg_replace_callback('~https?://[^\s<>"\']+~i', function ($match) {
            $parts = parse_url($match[0]);
            if (! is_array($parts) || ! isset($parts['host'])) {
                return '[URL OMITTED]';
            }

            return ($parts['scheme'] ?? 'https').'://'.$parts['host'].(isset($parts['port']) ? ':'.$parts['port'] : '').(preg_replace('~/(reset-password|token|verify-email)/[^/]+~i', '/$1/[REDACTED]', $parts['path'] ?? '') ?? '').(isset($parts['query']) ? '?[REDACTED]' : '');
        }, $text) ?? '[REDACTION ERROR]';
        $text = preg_replace('/SQLSTATE\[[^\]]*\].*/is', '[DATABASE ERROR DETAILS OMITTED]', $text) ?? '[REDACTION ERROR]';
        $text = preg_replace('/\b(Bearer|Basic)\s+[^\s"\'<>]+/i', '$1 [REDACTED]', $text) ?? '[REDACTION ERROR]';
        $text = preg_replace('/((?:password|passwd|secret|client_secret|access_token|refresh_token|token|api[_-]?key|authorization|cookie|session[_-]?id|csrf[_-]?token)["\']?\s*[:=]\s*)("(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\'|[^\s,;&}\]]+)/i', '$1[REDACTED]', $text) ?? '[REDACTION ERROR]';
        $text = preg_replace('/\b\d{18}\b/', '[NIP REDACTED]', $text) ?? '[REDACTION ERROR]';
        $text = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '[EMAIL REDACTED]', $text) ?? '[REDACTION ERROR]';

        // One event per line; prevent forged log lines and terminal control sequences.
        $text = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]|[\x{0080}-\x{009F}\x{2028}\x{2029}]|[\x{202A}-\x{202E}\x{2066}-\x{2069}]/u', '', mb_convert_encoding($text, 'UTF-8', 'UTF-8')) ?? '[REDACTION ERROR]';

        $text = str_replace(["\r", "\n", "\t", "\0", "\033"], ['\\r', '\\n', '\\t', '', ''], $text);

        return mb_strlen($text) > 16000 ? mb_substr($text, 0, 15980).'[TRUNCATED]' : $text;
    }
}
