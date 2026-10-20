<?php

namespace Tests\Unit;

use App\Services\Integration\CertificateHttpLogger;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class CertificateHttpLoggerTest extends TestCase
{
    public function test_exchange_keeps_metadata_and_never_logs_request_or_response_content(): void
    {
        Log::spy();
        $exchange = CertificateHttpLogger::request('https://user:private@prestige.test/api/post?token=secret#fragment',
            'application/x-www-form-urlencoded', ['kode' => '2659', 'nip' => '199210212019031005'], 2, ['payload' => 'private']);
        CertificateHttpLogger::response($exchange, new Response(Http::response(['message' => 'private upstream body'])->wait()), 'untrusted message');
        Log::shouldHaveReceived('info')->with('Permintaan sertifikat dikirim.', Mockery::on(fn ($c) => $c['endpoint'] === 'https://prestige.test/api/post'
            && $c['attempt'] === 2 && ! isset($c['parameters'], $c['payload'], $c['_started_at'])
            && $c['event'] === 'certificate.http.request'))->once();
        Log::shouldHaveReceived('info')->with('Respons layanan sertifikat diterima.', Mockery::on(fn ($c) => $c['exchange_id'] === $exchange['exchange_id']
            && $c['http_status'] === 200 && $c['duration_ms'] >= 0 && ! isset($c['data'], $c['raw_content'])
            && ! str_contains(json_encode($c), 'private')))->once();
    }

    public function test_server_errors_and_transport_failures_have_correct_levels_without_body(): void
    {
        Log::spy();
        $exchange = CertificateHttpLogger::request('https://prestige.test/api/get_data_sertifikat', 'multipart/form-data', [], 1);
        CertificateHttpLogger::response($exchange, new Response(Http::response('upstream private data', 502)->wait()), 'ignored');
        CertificateHttpLogger::failed($exchange, new ConnectionException('Timeout Bearer private-token'));
        Log::shouldHaveReceived('error')->with('Respons layanan sertifikat diterima.', Mockery::on(fn ($c) => $c['http_status'] === 502
            && ! isset($c['raw_content'], $c['data'])))->once();
        Log::shouldHaveReceived('warning')->with('Layanan sertifikat tidak memberikan respons.', Mockery::on(fn ($c) => $c['exchange_id'] === $exchange['exchange_id']
            && $c['exception_class'] === ConnectionException::class && ! str_contains(json_encode($c), 'private-token')))->once();
        CertificateHttpLogger::response($exchange, new Response(Http::response([], 401)->wait()), 'ignored');
        Log::shouldHaveReceived('warning')->with('Respons layanan sertifikat diterima.', Mockery::on(fn ($c) => $c['http_status'] === 401))->once();
    }
}
