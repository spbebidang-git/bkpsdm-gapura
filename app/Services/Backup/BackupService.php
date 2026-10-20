<?php

namespace App\Services\Backup;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\Process\Process;

class BackupService
{
    public const KINDS = ['logs', 'daily', 'weekly', 'files'];

    public function create(string $kind, callable $produce): string
    {
        if (! in_array($kind, self::KINDS, true)) {
            throw new RuntimeException('Invalid backup kind.');
        }
        $root = config('backup.root');
        $lock = null;
        $work = null;
        try {
            $this->checkConfiguration();
            $this->privateDirectory($root);
            $lock = fopen($root.'/.backup.lock', 'c');
            if ($lock === false || ! flock($lock, LOCK_EX | LOCK_NB)) {
                throw new RuntimeException('Another backup is running.');
            }
            chmod($root.'/.backup.lock', 0600);
            $work = $root.'/.work-'.bin2hex(random_bytes(16));
            $this->privateDirectory($work);
            $source = $work.'/archive.gz';
            $produce($source);
            if (! is_file($source) || filesize($source) === 0) {
                throw new RuntimeException('Backup source is empty.');
            }
            $name = $kind.'-'.now()->format('Y-m-d_H-i-s-u').'-'.bin2hex(random_bytes(8)).'.age';
            $partial = $work.'/encrypted.age';
            $args = [config('backup.age_binary'), '--encrypt'];
            foreach (config('backup.recipients') as $recipient) {
                $args[] = '--recipient';
                $args[] = $recipient;
            }
            array_push($args, '--output', $partial, $source);
            $this->run(new Process($args));
            if (! is_file($partial) || filesize($partial) < 100) {
                throw new RuntimeException('Encrypted backup is missing or incomplete.');
            }
            chmod($partial, 0600);
            // Remove plaintext staging before any potentially slow off-site transfer.
            if (! unlink($source)) {
                throw new RuntimeException('Cannot remove plaintext staging.');
            }
            $dir = $root.'/'.$kind;
            $this->privateDirectory($dir);
            $path = $dir.'/'.$name;
            if (! rename($partial, $path)) {
                throw new RuntimeException('Cannot publish encrypted backup.');
            }
            $meta = [
                'version' => 1, 'kind' => $kind, 'name' => $name,
                'created_at' => now()->toIso8601String(),
                'bytes' => filesize($path), 'sha256' => hash_file('sha256', $path),
                'remote_disk' => config('backup.remote_disk'),
                'remote_key' => trim(config('backup.remote_prefix'), '/').'/'.$kind.'/'.$name,
            ];
            if ($meta['remote_disk']) {
                $disk = Storage::disk($meta['remote_disk']);
                $stream = fopen($path, 'rb');
                try {
                    if (! $disk->put($meta['remote_key'], $stream, ['visibility' => 'private'])) {
                        throw new RuntimeException('Remote upload failed.');
                    }
                } finally {
                    fclose($stream);
                }
                $remote = $disk->readStream($meta['remote_key']);
                if (! is_resource($remote)) {
                    throw new RuntimeException('Cannot verify remote backup.');
                }
                try {
                    $hash = hash_init('sha256');
                    $bytes = hash_update_stream($hash, $remote);
                    if ($bytes !== $meta['bytes'] || ! hash_equals($meta['sha256'], hash_final($hash))) {
                        throw new RuntimeException('Remote checksum mismatch.');
                    }
                } finally {
                    fclose($remote);
                }
                if (! $disk->put($meta['remote_key'].'.json', json_encode($meta, JSON_THROW_ON_ERROR), ['visibility' => 'private'])) {
                    throw new RuntimeException('Remote manifest upload failed.');
                }
            }
            // A manifest is the commit marker: never prune until publication succeeds.
            $temporary = $work.'/manifest.json';
            $json = json_encode($meta, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
            if (file_put_contents($temporary, $json, LOCK_EX) !== strlen($json)) {
                throw new RuntimeException('Cannot write backup manifest.');
            }
            chmod($temporary, 0600);
            if (! rename($temporary, $path.'.json')) {
                throw new RuntimeException('Cannot publish backup manifest.');
            }
            $this->prune($kind, $path);
            Log::channel('backup')->info('Backup selesai dan terverifikasi.', ['event' => 'backup.completed','kind' => $kind, 'name' => $name, 'bytes' => $meta['bytes'], 'sha256' => $meta['sha256']]);

            return $path;
        } catch (\Throwable $e) {
            // Process exceptions may contain credentials or arbitrary server output.
            $this->alert('Backup failed', ['kind' => $kind, 'exception_class' => $e::class]);
            throw new RuntimeException('Backup failed. Check dependencies, configuration, disk capacity and any configured remote storage.', 0, $e);
        } finally {
            if ($work !== null && is_dir($work)) {
                File::deleteDirectory($work);
            }
            if (is_resource($lock)) {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
    }

    public function checkConfiguration(): void
    {
        $recipients = config('backup.recipients');
        if (! is_array($recipients) || $recipients === []) {
            throw new RuntimeException('BACKUP_AGE_RECIPIENTS is required.');
        }
        foreach ($recipients as $recipient) {
            if (! preg_match('/^age1[0-9a-z]{58}$/D', $recipient)) {
                throw new RuntimeException('Only native age public recipients are accepted.');
            }
        }
        $disk = config('backup.remote_disk');
        if (config('backup.require_remote') && ! $disk) {
            throw new RuntimeException('An off-site backup disk is required.');
        }
        if ($disk && (config('filesystems.disks.'.$disk.'.visibility') === 'public'
            || (app()->environment('production') && config('filesystems.disks.'.$disk.'.driver') === 'local'))) {
            throw new RuntimeException('Backup disk must be private and off-site in production.');
        }
        if ($disk && app()->environment('production')) {
            $remote = config('filesystems.disks.'.$disk, []);
            if (! in_array($remote['driver'] ?? null, ['s3', 'sftp'], true) || ($remote['visibility'] ?? null) !== 'private') {
                throw new RuntimeException('Production backups require an explicitly private S3 or SFTP disk.');
            }
            if (! empty($remote['endpoint']) && parse_url($remote['endpoint'], PHP_URL_SCHEME) !== 'https') {
                throw new RuntimeException('Off-site endpoint must use HTTPS.');
            }
        }
        $this->run(new Process([config('backup.age_binary'), '--version']));
    }

    public function dumpDatabase(string $destination): void
    {
        if (config('database.default') !== 'mysql') {
            throw new RuntimeException('Database backup currently requires MySQL.');
        }
        $cfg = config('database.connections.mysql');
        if (empty($cfg['database']) || empty($cfg['username'])) {
            throw new RuntimeException('MySQL configuration is incomplete.');
        }
        // A private option file avoids passwords in argv and MYSQL_PWD.
        $credentials = dirname($destination).'/mysql.cnf';
        $quote = fn ($value) => '"'.str_replace(['\\', '"', "\n", "\r"], ['\\\\', '\\"', '\\n', '\\r'], (string) $value).'"';
        $options = "[client]\nuser=".$quote($cfg['username'])."\npassword=".$quote($cfg['password'] ?? '')."\n";
        if (file_put_contents($credentials, $options, LOCK_EX) !== strlen($options) || ! chmod($credentials, 0600)) {
            throw new RuntimeException('Cannot create private MySQL option file.');
        }
        $args = ['mysqldump', '--defaults-extra-file='.$credentials, '--host='.($cfg['host'] ?? '127.0.0.1'), '--port='.($cfg['port'] ?? 3306), '--single-transaction', '--quick', '--routines', '--triggers', '--events', '--hex-blob', '--no-tablespaces'];
        if (! empty($cfg['unix_socket'])) {
            $args[] = '--socket='.$cfg['unix_socket'];
        }
        // Honor configured MySQL TLS options instead of silently losing transport protection.
        foreach ([\PDO::MYSQL_ATTR_SSL_CA => '--ssl-ca=', \PDO::MYSQL_ATTR_SSL_CERT => '--ssl-cert=', \PDO::MYSQL_ATTR_SSL_KEY => '--ssl-key='] as $option => $flag) {
            if (! empty($cfg['options'][$option])) {
                $args[] = $flag.$cfg['options'][$option];
            }
        }
        array_push($args, '--', $cfg['database']);
        $gz = gzopen($destination, 'wb6');
        if ($gz === false) {
            throw new RuntimeException('Cannot create compressed database dump.');
        }
        chmod($destination, 0600);
        $process = new Process($args, env: ['MYSQL_PWD' => false]);
        $bytes = 0;
        try {
            $process->setTimeout(config('backup.timeout'));
            $process->run(function ($type, $chunk) use ($gz, $process, &$bytes) {
                if ($type === Process::OUT) {
                    if (gzwrite($gz, $chunk) !== strlen($chunk)) {
                        throw new RuntimeException('Incomplete database dump write.');
                    }
                    $bytes += strlen($chunk);
                    $process->clearOutput();
                } else {
                    $process->clearErrorOutput();
                }
            });
            if (! $process->isSuccessful() || $bytes === 0) {
                throw new RuntimeException('Database export failed or was empty.');
            }
        } finally {
            $process->stop();
            $closed = gzclose($gz);
            unlink($credentials);
        }
        if (! $closed) {
            throw new RuntimeException('Cannot finalize compressed database dump.');
        }
    }

    public function archive(string $destination, array $roots): void
    {
        $base = realpath(base_path());
        $paths = [];
        foreach ($roots as $root) {
            $real = realpath($root);
            if ($real === false || ! is_dir($real) || is_link($root) || ! str_starts_with($real, $base.DIRECTORY_SEPARATOR)) {
                throw new RuntimeException('A configured backup source is missing or outside the application.');
            }
            $paths[] = substr($real, strlen($base) + 1);
        }
        if ($paths === []) {
            throw new RuntimeException('No file backup sources configured.');
        }
        // tar does not dereference symlinks. Failure (including changing files) is not ignored.
        $this->run(new Process(['tar', '-czf', $destination, '-C', $base, '--', ...$paths]));
        chmod($destination, 0600);
    }

    public function run(Process $process): void
    {
        $process->setTimeout(config('backup.timeout'));
        $process->disableOutput();
        if ($process->run() !== 0) {
            throw new RuntimeException('Backup dependency exited unsuccessfully.');
        }
    }

    public function alert(string $message, array $context = []): void
    {
        Log::channel('backup')->error('Backup memerlukan perhatian.', ['event' => 'backup.alert', 'reason' => $message] + $context);
        Log::channel('stderr')->error('Backup memerlukan perhatian.', ['event' => 'backup.alert', 'reason' => $message] + $context);
        if ($email = config('backup.alert_email')) {
            try {
                Mail::raw($message.' '.json_encode($context, JSON_THROW_ON_ERROR), fn ($mail) => $mail->to($email)->subject('[Gapura] Backup requires attention'));
            } catch (\Throwable) {
                Log::channel('stderr')->error('Peringatan backup gagal dikirim.', ['event' => 'backup.alert.delivery_failed']);
            }
        }
    }

    private function privateDirectory(string $path): void
    {
        if (is_link($path)) {
            throw new RuntimeException('Backup directory must not be a symbolic link.');
        }
        File::ensureDirectoryExists($path, 0700);
        if (! chmod($path, 0700)) {
            throw new RuntimeException('Cannot restrict backup directory permissions.');
        }
    }

    private function prune(string $kind, string $current): void
    {
        $keep = max(1, (int) config('backup.'.match ($kind) {
            'logs' => 'log_keep', 'daily' => 'daily_keep', 'weekly' => 'weekly_keep', 'files' => 'files_keep',
        }));
        $dir = config('backup.root').'/'.$kind;
        $manifests = collect(File::files($dir))->filter(fn ($file) => ! $file->isLink() && str_ends_with($file->getFilename(), '.age.json'))
            ->sortByDesc(fn ($file) => [$file->getPathname() === $current.'.json', $file->getFilename()])->values();
        foreach ($manifests->slice($keep) as $manifest) {
            File::delete(substr($manifest->getPathname(), 0, -5));
            File::delete($manifest->getPathname());
        }
        // Remote retention is managed by the bucket policy, never by application delete credentials.
    }
}
