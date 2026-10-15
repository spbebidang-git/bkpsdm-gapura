<?php

namespace App\Logging;

final class Redactor
{
    public function clean(mixed $value, int $depth = 0): mixed
    {
        if ($depth > 12) {
            return '[DEPTH LIMIT]';
        }
        if ($value instanceof \Throwable) {
            return ['class' => $value::class, 'message' => $this->text($value->getMessage()), 'code' => $value->getCode()];
        }
        if (is_object($value)) {
            // Never serialize arbitrary models, requests or exception arguments.
            return '[OBJECT '.$value::class.']';
        }
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                $safeKey = is_string($key) ? $this->text($key) : $key;
                $result[$safeKey] = preg_match('/password|passwd|secret|token|authorization|cookie|api.?key|credential|\bnip\b|nip_|_nip|peserta_sample|email|phone|address|payload|response|body|headers|bindings/i', (string) $key)
                    ? '[REDACTED]' : $this->clean($item, $depth + 1);
            }

            return $result;
        }

        return is_string($value) ? $this->text($value) : $value;
    }

    public function text(string $text): string
    {
        foreach (['app.key', 'database.connections.mysql.password', 'services.sso.client_secret', 'services.sso.admin_pass', 'services.sso.api_key', 'filesystems.disks.backup_s3.secret', 'filesystems.disks.s3.secret', 'services.kedaton.client_secret', 'services.kedaton.data_key'] as $key) {
            $secret = config($key);
            if (is_string($secret) && strlen($secret) >= 6) {
                $text = str_replace($secret, '[REDACTED]', $text);
            }
        }
        $text = preg_replace('/SQLSTATE\[[^\]]*\].*/is', '[DATABASE ERROR DETAILS OMITTED]', $text) ?? '[REDACTION ERROR]';
        $text = preg_replace('/\b(Bearer|Basic)\s+[^\s"\'<>]+/i', '$1 [REDACTED]', $text) ?? '[REDACTION ERROR]';
        $text = preg_replace('/((?:password|passwd|client_secret|access_token|refresh_token|api[_-]?key|authorization|cookie)["\']?\s*[:=]\s*)("(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\'|[^\s,;&}\]]+)/i', '$1[REDACTED]', $text) ?? '[REDACTION ERROR]';
        $text = preg_replace('/\b\d{18}\b/', '[NIP REDACTED]', $text) ?? '[REDACTION ERROR]';
        $text = preg_replace('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', '[EMAIL REDACTED]', $text) ?? '[REDACTION ERROR]';

        // One event per line; prevent forged log lines and terminal control sequences.
        return mb_substr(str_replace(["\r", "\n", "\t", "\0", "\033"], ['\\r', '\\n', '\\t', '', ''], $text), 0, 16000);
    }
}
