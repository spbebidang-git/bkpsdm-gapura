<?php

namespace App\Services\Integration;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

final class CertificateHttpLogger
{
    public static function request(string $endpoint, string $encoding, array $parameters, int $attempt, array $context = []): array
    {
        $parts = parse_url($endpoint);
        $context = array_intersect_key($context, array_flip(['course_id', 'learn_id', 'kode']));
        $context += [
            'exchange_id' => (string) Str::uuid(),
            'endpoint' => is_array($parts) ? ($parts['scheme'] ?? 'https').'://'.($parts['host'] ?? '').(isset($parts['port']) ? ':'.$parts['port'] : '').($parts['path'] ?? '') : '[INVALID URL]',
            'operation' => basename($parts['path'] ?? ''),
            'method' => 'POST',
            'encoding' => $encoding,
            'attempt' => $attempt,
            '_started_at' => hrtime(true),
        ];
        Log::info('Permintaan sertifikat dikirim.', self::metadata($context) + ['event' => 'certificate.http.request']);

        return $context;
    }

    public static function response(array $context, Response $response, string $message): void
    {
        $level = $response->serverError() ? 'error' : ($response->failed() ? 'warning' : 'info');
        Log::$level('Respons layanan sertifikat diterima.', self::metadata($context) + [
            'event' => 'certificate.http.response',
            'http_status' => $response->status(),
            'duration_ms' => self::duration($context),
        ]);
    }

    public static function failed(array $context, Throwable $exception): void
    {
        // The caller owns exception reporting; this event records transport metadata only.
        Log::warning('Layanan sertifikat tidak memberikan respons.', self::metadata($context) + [
            'event' => 'certificate.http.failed',
            'duration_ms' => self::duration($context),
            'exception_class' => $exception::class,
        ]);
    }

    private static function metadata(array $context): array
    {
        return array_intersect_key($context, array_flip(['course_id', 'learn_id', 'kode', 'exchange_id', 'endpoint', 'operation', 'method', 'encoding', 'attempt']));
    }

    private static function duration(array $context): float
    {
        return round(max(0, hrtime(true) - ($context['_started_at'] ?? hrtime(true))) / 1_000_000, 2);
    }
}
