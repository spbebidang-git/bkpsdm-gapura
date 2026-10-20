<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\Management\LogController;
use App\Http\Middleware\IsAdminMiddleware;
use App\Logging\Redactor;
use App\Logging\SecureLogs;
use App\Models\Identity\User;
use App\Services\Backup\BackupService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class LogBackupTest extends TestCase
{
    private string $temporary;

    private string $oldStorage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->temporary = sys_get_temp_dir().'/gapura-backup-test-'.bin2hex(random_bytes(8));
        $this->oldStorage = storage_path();
        File::ensureDirectoryExists($this->temporary.'/logs', 0700);
        app()->useStoragePath($this->temporary);
        config(['backup.root' => $this->temporary.'/backups', 'backup.recipients' => [], 'backup.remote_disk' => null, 'backup.require_remote' => false, 'backup.alert_email' => null]);
        foreach (['backup', 'stderr', 'audit', 'auth'] as $channel) {
            config(['logging.channels.'.$channel => ['driver' => 'null']]);
            Log::forgetChannel($channel);
        }
        Mail::fake();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        app()->useStoragePath($this->oldStorage);
        File::deleteDirectory($this->temporary);
        parent::tearDown();
    }

    private function request(array $query, bool $canBackup = false): Request
    {
        $request = Request::create('/admin/management/log/download', 'GET', $query);
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('hasRole')->with('super_admin')->andReturn(false);
        $user->shouldReceive('can')->with('backup.download')->andReturn($canBackup);
        $user->shouldReceive('getAuthIdentifier')->andReturn('test-user-id');
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    private function assertHttpStatus(int $status, callable $call): void
    {
        try {
            $call();
            $this->fail('Expected HTTP '.$status);
        } catch (HttpException $e) {
            $this->assertSame($status, $e->getStatusCode());
        }
    }

    private function configureAge(): string
    {
        $binary = getenv('TEST_AGE_BINARY') ?: (new \Symfony\Component\Process\ExecutableFinder)->find('age');
        if (! $binary || ! is_executable($binary)) {
            $this->markTestSkipped('Install age and age-keygen or set TEST_AGE_BINARY to run real encryption tests.');
        }
        $identity = $this->temporary.'/identity.txt';
        (new Process([dirname($binary).'/age-keygen', '-o', $identity]))->mustRun();
        $public = new Process([dirname($binary).'/age-keygen', '-y', $identity]);
        $public->mustRun();
        config(['backup.age_binary' => $binary, 'backup.recipients' => [trim($public->getOutput())]]);

        return $identity;
    }

    public function test_secrets_are_redacted_before_the_log_file_is_written(): void
    {
        config(['app.key' => 'private-application-key']);
        config(['logging.channels.secure_test' => ['driver' => 'single', 'path' => $this->temporary.'/logs/secure.log', 'tap' => [SecureLogs::class], 'permission' => 0600, 'locking' => true]]);
        $logger = Log::channel('secure_test');
        $logger->error("Bearer token-value\nforged event nip=198001012020011001 person@example.com private-application-key", [
            'password' => 'secret-password', 'nested' => ['refresh_token' => 'secret-refresh'],
            'exception' => new \RuntimeException('password="secret exception password"'),
            'response' => ['any' => 'sensitive response'],
            'model' => (object) ['password' => 'object-secret'],
            'safe_count' => 3,
        ]);
        $raw = File::get($this->temporary.'/logs/secure.log');
        foreach (['token-value', '198001012020011001', 'person@example.com', 'private-application-key', 'secret-password', 'secret-refresh', 'secret exception password', 'sensitive response', 'object-secret'] as $secret) {
            $this->assertStringNotContainsString($secret, $raw);
        }
        $this->assertCount(1, array_filter(explode("\n", $raw)));
        $this->assertSame(3, json_decode($raw, true)['context']['safe_count']);
        $this->assertSame(0600, fileperms($this->temporary.'/logs/secure.log') & 0777);
    }

    public function test_redactor_handles_quoted_secrets_and_control_characters(): void
    {
        $text = app(Redactor::class)->text('access_token="secret with spaces" password=secret-value api_key=hidden&ok=1'."\n\033");
        foreach (['secret with spaces', 'secret-value', 'hidden', "\n", "\033"] as $secret) {
            $this->assertStringNotContainsString($secret, $text);
        }
        $this->assertStringContainsString('ok=1', $text);
    }

    public function test_raw_log_download_is_sanitized_and_not_cached(): void
    {
        File::put(storage_path('logs/legacy.log'), "Bearer legacy-token nip=198001012020011001\npassword=old-secret\n");
        $response = app(LogController::class)->download($this->request(['type' => 'log', 'file' => 'legacy.log']));
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();
        foreach (['legacy-token', '198001012020011001', 'old-secret'] as $secret) {
            $this->assertStringNotContainsString($secret, $content);
        }
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_download_rejects_traversal_symlinks_and_non_log_files(): void
    {
        File::put($this->temporary.'/private.txt', 'private');
        symlink($this->temporary.'/private.txt', storage_path('logs/link.log'));
        foreach (['../private.txt', 'link.log', 'private.txt'] as $file) {
            $this->assertHttpStatus(404, fn () => app(LogController::class)->download($this->request(['type' => 'log', 'file' => $file])));
        }
    }

    public function test_view_permission_cannot_download_any_backup(): void
    {
        $this->assertHttpStatus(403, fn () => app(LogController::class)->download($this->request(['type' => 'daily', 'file' => 'backup.age'])));
    }

    public function test_legacy_plaintext_backups_cannot_be_downloaded_even_with_permission(): void
    {
        File::ensureDirectoryExists(config('backup.root').'/daily');
        File::put(config('backup.root').'/daily/old.sql.gz', gzencode('database secrets'));
        $this->assertHttpStatus(404, fn () => app(LogController::class)->download($this->request(['type' => 'daily', 'file' => 'old.sql.gz'], true)));
    }

    public function test_admin_middleware_still_requires_log_permission_for_download_route(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('getRoleNames')->andReturn(collect(['administrator']));
        $user->shouldReceive('hasRole')->with('super_admin')->andReturn(false);
        $user->shouldReceive('can')->with('log.view')->andReturn(false);
        $request = Request::create('/admin/management/log/download');
        $route = new Route('GET', 'admin/management/log/download', fn () => 'ok');
        $route->name('admin.management.log.download');
        $request->setRouteResolver(fn () => $route);
        $request->setUserResolver(fn () => $user);
        $this->assertHttpStatus(403, fn () => app(IsAdminMiddleware::class)->handle($request, fn () => response('unexpected')));
    }

    public function test_backup_refuses_to_produce_plaintext_when_recipient_is_missing(): void
    {
        $called = false;
        try {
            app(BackupService::class)->create('daily', function () use (&$called) {
                $called = true;
            });
            $this->fail('Missing recipient must fail closed.');
        } catch (\RuntimeException) {
            $this->assertFalse($called);
            $this->assertDirectoryDoesNotExist(config('backup.root'));
        }
    }

    public function test_real_age_backup_can_be_decrypted_and_remote_copy_matches(): void
    {
        $identity = $this->configureAge();
        Storage::fake('backup_test');
        config(['backup.remote_disk' => 'backup_test', 'backup.require_remote' => true]);
        $payload = gzencode('CREATE TABLE recovered (id INT);');
        $path = app(BackupService::class)->create('daily', fn ($file) => File::put($file, $payload));
        $meta = json_decode(File::get($path.'.json'), true);
        $this->assertSame(hash_file('sha256', $path), $meta['sha256']);
        $this->assertSame(File::get($path), Storage::disk('backup_test')->get($meta['remote_key']));
        $restored = $this->temporary.'/restored.gz';
        (new Process([config('backup.age_binary'), '--decrypt', '-i', $identity, '-o', $restored, $path]))->mustRun();
        $this->assertSame('CREATE TABLE recovered (id INT);', gzdecode(File::get($restored)));
        $this->assertSame([], glob(config('backup.root').'/.work-*'));
        $this->assertSame(0600, fileperms($path) & 0777);
        $this->assertSame(0700, fileperms(dirname($path)) & 0777);
        $response = app(LogController::class)->download($this->request(['type' => 'daily', 'file' => basename($path)], true));
        $this->assertSame(200, $response->getStatusCode());
        File::append($path, 'corruption');
        $this->assertHttpStatus(404, fn () => app(LogController::class)->download($this->request(['type' => 'daily', 'file' => basename($path)], true)));
    }

    public function test_remote_failure_does_not_commit_or_prune_backup_and_cleans_plaintext(): void
    {
        $this->configureAge();
        $disk = Mockery::mock();
        $disk->shouldReceive('put')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('failed_disk')->andReturn($disk);
        config(['backup.remote_disk' => 'failed_disk']);
        try {
            app(BackupService::class)->create('daily', fn ($file) => File::put($file, gzencode('private data')));
            $this->fail('Remote write failure must fail backup.');
        } catch (\RuntimeException) {
            $this->assertSame([], glob(config('backup.root').'/daily/*.json'));
            $this->assertSame([], glob(config('backup.root').'/.work-*'));
        }
    }

    public function test_remote_checksum_mismatch_is_not_reported_as_success(): void
    {
        $this->configureAge();
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, 'corrupted remote bytes');
        rewind($stream);
        $disk = Mockery::mock();
        $disk->shouldReceive('put')->once()->andReturn(true);
        $disk->shouldReceive('readStream')->once()->andReturn($stream);
        Storage::shouldReceive('disk')->with('corrupt_disk')->andReturn($disk);
        config(['backup.remote_disk' => 'corrupt_disk']);
        try {
            app(BackupService::class)->create('daily', fn ($file) => File::put($file, gzencode('private data')));
            $this->fail('Remote corruption must fail backup.');
        } catch (\RuntimeException) {
            $this->assertSame([], glob(config('backup.root').'/daily/*.json'));
        }
    }

    public function test_repeated_runs_keep_distinct_names_and_retain_only_committed_backups(): void
    {
        $this->configureAge();
        config(['backup.daily_keep' => 1]);
        $first = app(BackupService::class)->create('daily', fn ($file) => File::put($file, gzencode('first')));
        Carbon::setTestNow(now()->addSeconds(2));
        $second = app(BackupService::class)->create('daily', fn ($file) => File::put($file, gzencode('second')));
        $this->assertNotSame($first, $second);
        $this->assertFileDoesNotExist($first);
        $this->assertFileExists($second.'.json');
    }

    public function test_health_check_fails_when_verified_backups_are_missing(): void
    {
        $this->artisan('backup:health')->assertFailed();
    }

    public function test_local_production_backups_pass_health_without_remote_storage(): void
    {
        $originalEnvironment = app()->environment();
        app()->instance('env', 'production');
        config([
            'backup.recipients' => ['age1'.str_repeat('q', 58)],
            'backup.remote_disk' => null,
            'backup.require_remote' => false,
            'backup.alert_email' => 'backup@example.test',
            'mail.default' => 'smtp',
        ]);
        // Isolate the configuration/health contract from the external age binary.
        $service = Mockery::mock(BackupService::class)->makePartial();
        $service->shouldReceive('run')->once()->with(Mockery::on(fn ($process) => str_contains($process->getCommandLine(), '--version')));
        app()->instance(BackupService::class, $service);
        foreach (BackupService::KINDS as $kind) {
            $dir = config('backup.root').'/'.$kind;
            File::ensureDirectoryExists($dir, 0700);
            $name = $kind.'-test.age';
            File::put($dir.'/'.$name, 'test encrypted archive fixture');
            File::put($dir.'/'.$name.'.json', json_encode([
                'kind' => $kind, 'name' => $name, 'created_at' => now()->toIso8601String(),
                'bytes' => filesize($dir.'/'.$name), 'remote_disk' => null,
            ], JSON_THROW_ON_ERROR));
        }
        try {
            $this->artisan('backup:health')->assertSuccessful();
            Mail::assertNothingSent();
        } finally {
            app()->instance('env', $originalEnvironment);
        }
    }

    public function test_explicit_remote_requirement_still_rejects_missing_disk(): void
    {
        config(['backup.recipients' => ['age1'.str_repeat('q', 58)], 'backup.require_remote' => true]);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('An off-site backup disk is required.');
        app(BackupService::class)->checkConfiguration();
    }

    public function test_failed_database_or_log_command_returns_nonzero(): void
    {
        $this->artisan('db:backup')->assertFailed();
        $this->artisan('logs:backup')->assertFailed();
        $this->artisan('files:backup')->assertFailed();
    }

    public function test_failed_producer_cleans_staging_and_preserves_the_previous_backup(): void
    {
        $this->configureAge();
        $previous = app(BackupService::class)->create('daily', fn ($file) => File::put($file, gzencode('previous')));
        try {
            app(BackupService::class)->create('daily', function ($file) {
                File::put($file, 'incomplete private dump');
                throw new \RuntimeException('producer failed');
            });
            $this->fail('Producer failure must fail the backup.');
        } catch (\RuntimeException) {
            $this->assertFileExists($previous.'.json');
            $this->assertSame([], glob(config('backup.root').'/.work-*'));
            $this->assertCount(1, glob(config('backup.root').'/daily/*.age'));
        }
    }

    public function test_concurrent_manual_backup_is_rejected_before_production(): void
    {
        $this->configureAge();
        File::ensureDirectoryExists(config('backup.root'), 0700);
        $lock = fopen(config('backup.root').'/.backup.lock', 'c');
        flock($lock, LOCK_EX | LOCK_NB);
        $called = false;
        try {
            app(BackupService::class)->create('daily', function () use (&$called) {
                $called = true;
            });
            $this->fail('An overlapping backup must fail.');
        } catch (\RuntimeException) {
            $this->assertFalse($called);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public function test_real_mysql_dump_encryption_and_restore_preserve_records(): void
    {
        if (! getenv('TEST_MYSQL_RESTORE') || ! str_starts_with((string) config('database.connections.mysql.database'), 'gapura_security_test_')) {
            $this->markTestSkipped('Run the isolated MySQL suite to exercise a real disposable database restore.');
        }
        $identity = $this->configureAge();
        $cfg = config('database.connections.mysql');
        $control = DB::connection('mysql')->getPdo();
        $source = 'gapura_restore_source_'.bin2hex(random_bytes(6));
        $target = 'gapura_restore_target_'.bin2hex(random_bytes(6));
        try {
            $control->exec('CREATE DATABASE `'.$source.'`');
            $control->exec('CREATE DATABASE `'.$target.'`');
            $control->exec('CREATE TABLE `'.$source.'`.probe (id INT PRIMARY KEY, content TEXT) ENGINE=InnoDB');
            $expected = 'recovery sample with unicode: berhasil ✓';
            $statement = $control->prepare('INSERT INTO `'.$source.'`.probe VALUES (1, ?)');
            $statement->execute([$expected]);
            config(['database.default' => 'mysql', 'database.connections.mysql.database' => $source]);
            $service = app(BackupService::class);
            $path = $service->create('daily', $service->dumpDatabase(...));
            $compressed = $this->temporary.'/recovery.sql.gz';
            (new Process([config('backup.age_binary'), '--decrypt', '-i', $identity, '-o', $compressed, $path]))->mustRun();
            $sql = gzdecode(File::get($compressed));
            $this->assertNotFalse($sql);
            // CLI option file stays private; never put the password in process argv.
            $options = $this->temporary.'/restore.cnf';
            $quote = fn ($value) => '"'.str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\n', '\\r'], (string) $value).'"';
            File::put($options, "[client]\nuser=".$quote($cfg['username'])."\npassword=".$quote($cfg['password'] ?? '')."\n");
            chmod($options, 0600);
            $args = ['mysql', '--defaults-extra-file='.$options, '--host='.($cfg['host'] ?? '127.0.0.1'), '--port='.($cfg['port'] ?? 3306)];
            if (! empty($cfg['unix_socket'])) {
                $args[] = '--socket='.$cfg['unix_socket'];
            }
            $args[] = $target;
            $import = new Process($args, env: ['MYSQL_PWD' => false], input: $sql);
            $import->mustRun();
            $this->assertSame($expected, $control->query('SELECT content FROM `'.$target.'`.probe WHERE id=1')->fetchColumn());
            $this->assertSame([], glob(config('backup.root').'/.work-*'));
        } finally {
            $control->exec('DROP DATABASE IF EXISTS `'.$source.'`');
            $control->exec('DROP DATABASE IF EXISTS `'.$target.'`');
            config(['database.connections.mysql' => $cfg]);
        }
    }

    public function test_emergency_fallback_also_redacts_sensitive_values(): void
    {
        config([
            'logging.channels.emergency.path' => $this->temporary.'/logs/emergency.log',
            'logging.channels.broken' => ['driver' => 'unknown-driver'],
        ]);
        Log::channel('broken')->error('Bearer emergency-secret', ['password' => 'emergency-password']);
        $raw = File::get($this->temporary.'/logs/emergency.log');
        $this->assertStringNotContainsString('emergency-secret', $raw);
        $this->assertStringNotContainsString('emergency-password', $raw);
        $this->assertSame(0600, fileperms($this->temporary.'/logs/emergency.log') & 0777);
    }

    public function test_on_demand_logger_also_redacts_without_a_configured_tap(): void
    {
        $logger = Log::build(['driver' => 'single', 'path' => $this->temporary.'/logs/ondemand.log']);
        $logger->error('Bearer ondemand-secret', ['password' => 'ondemand-password']);
        $raw = File::get($this->temporary.'/logs/ondemand.log');
        $this->assertStringNotContainsString('ondemand-secret', $raw);
        $this->assertStringNotContainsString('ondemand-password', $raw);
    }

    public function test_file_archive_can_be_decrypted_without_following_symlinks(): void
    {
        $identity = $this->configureAge();
        $source = base_path('storage/framework/testing/backup-fixture-'.bin2hex(random_bytes(8)));
        File::ensureDirectoryExists($source, 0700);
        try {
            File::put($source.'/document.txt', 'document restored correctly');
            File::put($this->temporary.'/outside.txt', 'outside secret must not enter archive');
            symlink($this->temporary.'/outside.txt', $source.'/link.txt');
            $service = app(BackupService::class);
            $path = $service->create('files', fn ($file) => $service->archive($file, [$source]));
            $restored = $this->temporary.'/restored.tar.gz';
            (new Process([config('backup.age_binary'), '--decrypt', '-i', $identity, '-o', $restored, $path]))->mustRun();
            $tar = gzdecode(File::get($restored));
            $this->assertStringContainsString('document restored correctly', $tar);
            $this->assertStringNotContainsString('outside secret must not enter archive', $tar);
            $listing = new Process(['tar', '-tzf', $restored]);
            $listing->mustRun();
            $this->assertStringContainsString('/document.txt', $listing->getOutput());
            $this->assertStringContainsString('/link.txt', $listing->getOutput());
        } finally {
            File::deleteDirectory($source);
        }
    }
}
