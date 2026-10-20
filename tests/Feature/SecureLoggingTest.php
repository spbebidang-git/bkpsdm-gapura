<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\Management\LogController;
use App\Http\Middleware\RequestLogContext;
use App\Logging\LogContext;
use App\Logging\LogReader;
use App\Logging\Redactor;
use App\Logging\SafeJsonFormatter;
use App\Logging\SecureLogs;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Mockery;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Tests\TestCase;

class SecureLoggingTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = sys_get_temp_dir().'/gapura-logging-'.bin2hex(random_bytes(8));
        mkdir($this->directory, 0700);
    }

    protected function tearDown(): void
    {
        Log::forgetChannel('security_test');
        foreach (glob($this->directory.'/*') as $file) {
            unlink($file);
        }
        rmdir($this->directory);
        parent::tearDown();
    }

    private function logger()
    {
        config(['logging.channels.security_test' => ['driver' => 'single', 'path' => $this->directory.'/test.log', 'tap' => [SecureLogs::class], 'replace_placeholders' => true, 'permission' => 0600, 'locking' => true]]);

        return Log::channel('security_test');
    }

    public function test_interpolation_and_context_are_redacted_before_and_after_processors(): void
    {
        $logger = $this->logger();
        $logger->info("Login {password}\nforged\033[31m", ['password' => 'uniquesecret', 'sessionId' => 'session-secret', 'nested' => ['api-key' => 'key-secret'], 'url' => 'https://user:pass@example.test/a?auth=private', 'safe_count' => 4]);
        $raw = file_get_contents($this->directory.'/test.log');
        foreach (['uniquesecret', 'session-secret', 'key-secret', 'user:pass', 'auth=private'] as $secret) {
            $this->assertStringNotContainsString($secret, $raw);
        }
        $record = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame(4, $record['context']['safe_count']);
        $this->assertSame(1, substr_count($raw, "\n"));
        $this->assertSame(0600, fileperms($this->directory.'/test.log') & 0777);
    }

    public function test_oversized_and_deep_records_stay_valid_json_under_limit(): void
    {
        $this->logger()->error(str_repeat('é', 20000), ['large' => array_fill(0, 200, str_repeat('x', 4000)), 'exception' => new \RuntimeException('Bearer sensitive')]);
        $raw = file_get_contents($this->directory.'/test.log');
        $this->assertLessThanOrEqual(SafeJsonFormatter::MAX_BYTES, strlen($raw));
        $record = json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        $this->assertTrue($record['context']['truncated']);
        $deep = 'hidden';
        for ($i = 0; $i < 20; $i++) {
            $deep = ['next' => $deep];
        }
        $this->assertStringContainsString('DEPTH LIMIT', json_encode(app(Redactor::class)->clean($deep)));
        $this->assertStringNotContainsString('sensitive', $raw);
    }

    public function test_exception_preserves_locations_without_arguments(): void
    {
        $exception = new \RuntimeException('password=private');
        $safe = app(Redactor::class)->clean($exception);
        $this->assertStringNotContainsString('private', json_encode($safe));
        $this->assertStringNotContainsString(base_path(), $safe['file']);
        $this->assertLessThanOrEqual(20, count($safe['frames']));
        foreach ($safe['frames'] as $frame) {
            $this->assertArrayNotHasKey('args', $frame);
        }
    }

    public function test_http_success_errors_and_repeated_requests_have_unique_server_ids(): void
    {
        Route::get('/logging-probe', fn () => response('ok'));
        Route::get('/logging-failure', fn () => abort(500));
        $first = $this->withHeader('X-Request-ID', 'untrusted')->get('/logging-probe')->assertOk()->headers->get('X-Request-ID');
        $second = $this->get('/logging-failure')->assertStatus(500)->headers->get('X-Request-ID');
        $this->assertMatchesRegularExpression('/^[a-f0-9-]{36}$/', $first);
        $this->assertMatchesRegularExpression('/^[a-f0-9-]{36}$/', $second);
        $this->assertNotSame($first, $second);
        $this->assertSame([], app(LogContext::class)->all());
        $request = Request::create('/test');
        try {
            app(RequestLogContext::class)->handle($request, fn () => throw new \RuntimeException('test'));
        } catch (\RuntimeException) {
        }
        $this->assertSame([], app(LogContext::class)->all());
    }

    public function test_commands_restore_parent_and_jobs_do_not_leak_context_on_success_or_failure(): void
    {
        $context = app(LogContext::class);
        $context->begin('request', ['request_id' => 'request-one']);
        $outer = new ArrayInput([]);
        $inner = new ArrayInput([]);
        $output = new NullOutput;
        Event::dispatch(new CommandStarting('outer', $outer, $output));
        $parent = $context->all();
        Event::dispatch(new CommandStarting('inner', $inner, $output));
        $this->assertSame($parent['process_id'], $context->all()['parent_process_id']);
        Event::dispatch(new CommandFinished('inner', $inner, $output, 0));
        $this->assertSame($parent, $context->all());
        Event::dispatch(new CommandFinished('outer', $outer, $output, 1));
        $job = Mockery::mock(Job::class);
        $job->shouldReceive('payload')->andReturn(['gapura_log_context' => $parent]);
        $job->shouldReceive('getJobId')->andReturn('job-one');
        Event::dispatch(new JobProcessing('sync', $job));
        $this->assertSame('request-one', $context->all()['request_id']);
        $this->assertSame($parent['process_id'], $context->all()['parent_process_id']);
        Event::dispatch(new JobProcessed('sync', $job));
        $this->assertSame(['request_id' => 'request-one'], $context->all());
        $context->end('request');
        $other = Mockery::mock(Job::class);
        $other->shouldReceive('payload')->andReturn([]);
        $other->shouldReceive('getJobId')->andReturn('job-two');
        Event::dispatch(new JobProcessing('sync', $other));
        $this->assertArrayNotHasKey('request_id', $context->all());
        Event::dispatch(new JobExceptionOccurred('sync', $other, new \RuntimeException));
        $this->assertSame([], $context->all());
    }

    public function test_real_nested_artisan_calls_restore_context_after_an_exception(): void
    {
        app(Kernel::class)->rerouteSymfonyCommandEvents();
        $seen = [];
        Artisan::command('logging:inner', function () use (&$seen) {
            $seen['inner'] = app(LogContext::class)->all();
            throw new \RuntimeException('probe');
        });
        Artisan::command('logging:outer', function () use (&$seen) {
            $seen['outer'] = app(LogContext::class)->all();
            try {
                Artisan::call('logging:inner');
            } catch (\RuntimeException) {
            }
            $seen['restored'] = app(LogContext::class)->all();

            return 0;
        });
        $context = app(LogContext::class);
        $context->begin('request', ['request_id' => 'request-probe']);
        Artisan::call('logging:outer');
        $this->assertSame($seen['outer'], $seen['restored']);
        $this->assertSame($seen['outer']['process_id'], $seen['inner']['parent_process_id']);
        $this->assertSame('request-probe', $seen['inner']['request_id']);
        $this->assertSame(['request_id' => 'request-probe'], $context->all());
        $context->end('request');
    }

    public function test_actual_queue_payload_propagates_correlation_and_restores_after_failure(): void
    {
        LoggingProbeJob::$observed = [];
        $context = app(LogContext::class);
        $context->begin('request', ['request_id' => 'request-queued', 'process_id' => 'process-dispatch']);
        Queue::connection('sync')->push(new LoggingProbeJob);
        $this->assertSame('request-queued', LoggingProbeJob::$observed[0]['request_id']);
        $this->assertSame('process-dispatch', LoggingProbeJob::$observed[0]['parent_process_id']);
        $this->assertSame('process-dispatch', $context->all()['process_id']);
        $context->end('request');
        try {
            Queue::connection('sync')->push(new LoggingProbeJob(true));
        } catch (\RuntimeException $exception) {
            $logged = app(ExceptionHandler::class)->buildContextForException($exception);
            $this->assertSame(LoggingProbeJob::$observed[1]['process_id'], $logged['process_id']);
            $this->logger();
            $context->process('worker', ['command' => 'queue:work']);
            Log::channel('security_test')->error('Job gagal.', $logged + ['exception' => $exception]);
            $record = json_decode(file_get_contents($this->directory.'/test.log'), true, flags: JSON_THROW_ON_ERROR);
            $this->assertSame(LoggingProbeJob::$observed[1]['process_id'], $record['extra']['process_id']);
            $context->end('worker');
        }
        $this->assertArrayNotHasKey('request_id', LoggingProbeJob::$observed[1]);
        $this->assertNotSame(LoggingProbeJob::$observed[0]['process_id'], LoggingProbeJob::$observed[1]['process_id']);
        $this->assertSame([], $context->all());
    }

    public function test_http_log_matches_response_request_id_and_cannot_be_overridden(): void
    {
        $this->logger();
        Route::get('/logging-context-probe', function () {
            Log::channel('security_test')->info('Permintaan diperiksa.', ['event' => 'logging.checked']);

            return response('ok');
        });
        $response = $this->withHeader('X-Request-ID', 'attacker')->get('/logging-context-probe')->assertOk();
        $record = json_decode(file_get_contents($this->directory.'/test.log'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame($response->headers->get('X-Request-ID'), $record['extra']['request_id']);
        $this->assertSame([], app(LogContext::class)->all());
    }

    public function test_log_page_returns_filtered_entries_and_private_headers(): void
    {
        $original = storage_path();
        app()->useStoragePath($this->directory);
        mkdir($this->directory.'/logs', 0700);
        $id = '550e8400-e29b-41d4-a716-446655440000';
        file_put_contents($this->directory.'/logs/test.log', json_encode(['level_name' => 'ERROR', 'message' => 'Uji aman', 'context' => ['apiKey' => 'unknownsecret'], 'extra' => ['request_id' => $id]])."\n");
        Route::get('/logging-view-probe', [LogController::class, 'index']);
        try {
            $response = $this->get('/logging-view-probe?'.http_build_query(['file' => 'test.log', 'level' => 'error', 'q' => 'Uji', 'request_id' => $id]), ['X-Inertia' => 'true']);
            $response->assertOk()->assertJsonPath('props.entries.0.context.apiKey', '[REDACTED]')->assertJsonPath('props.entries.0.request_id', $id)->assertJsonPath('props.reading.available', true);
            $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
            $this->get('/logging-view-probe?file=../test.log', ['X-Inertia' => 'true'])->assertOk()->assertJsonPath('props.reading.available', false);
            $this->getJson('/logging-view-probe?request_id=invalid')->assertUnprocessable();
            $this->getJson('/logging-view-probe?level=invalid')->assertUnprocessable();
        } finally {
            app()->useStoragePath($original);
            unlink($this->directory.'/logs/test.log');
            rmdir($this->directory.'/logs');
        }
    }

    public function test_reader_filters_redacted_json_and_handles_legacy_malformed_and_large_files(): void
    {
        $path = $this->directory.'/reader.log';
        file_put_contents($path, str_repeat('x', 600000)."\n".json_encode(['datetime' => '2026-10-17T09:00:00+07:00', 'level_name' => 'ERROR', 'message' => '<script>alert(1)</script>', 'context' => ['password' => 'not-a-known-secret'], 'extra' => ['request_id' => 'request-1']])."\n[2026-10-17 09:00:00] production.INFO: Aman\n{\"password\":\"malformedsecret\"\n");
        $reader = app(LogReader::class);
        $result = $reader->tail($path, ['level' => 'error', 'q' => 'alert', 'request_id' => 'request-1']);
        $this->assertTrue($result['truncated']);
        $this->assertCount(1, $result['entries']);
        $this->assertSame('<script>alert(1)</script>', $result['entries'][0]['message']);
        $this->assertSame('[REDACTED]', $result['entries'][0]['context']['password']);
        ob_start();
        $reader->download($path);
        $download = ob_get_clean();
        $this->assertStringNotContainsString('not-a-known-secret', $download);
        $this->assertStringNotContainsString('malformedsecret', $download);
        $this->assertStringContainsString('Oversized log record omitted', $download);
        $this->assertStringContainsString('production.INFO: Aman', $download);
        $this->assertFalse($reader->tail(null, [])['available']);
        file_put_contents($path, str_repeat(json_encode(['message' => 'test', 'level_name' => 'INFO'])."\n", 250));
        $this->assertCount(200, $reader->tail($path, [])['entries']);
    }
}

class LoggingProbeJob implements ShouldQueue
{
    public static array $observed = [];

    public function __construct(public bool $fail = false) {}

    public function handle(): void
    {
        self::$observed[] = app(LogContext::class)->all();
        if ($this->fail) {
            throw new \RuntimeException('queue probe');
        }
    }
}
