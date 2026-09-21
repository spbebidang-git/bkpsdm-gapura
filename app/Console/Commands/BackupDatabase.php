<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup {--weekly : Weekly retention set}';

    protected $description = 'Backup MySQL database with mysqldump (daily/weekly retention)';

    public function handle(): int
    {
        $connection = config('database.default');
        if ($connection !== 'mysql') {
            $this->error("db:backup only supports mysql (current: {$connection}).");
            Log::channel('backup')->error('db:backup skipped: not mysql', ['connection' => $connection]);

            return self::FAILURE;
        }

        $cfg = config('database.connections.mysql');
        $database = $cfg['database'] ?? null;
        $username = $cfg['username'] ?? null;
        $password = $cfg['password'] ?? '';
        $host = $cfg['host'] ?? '127.0.0.1';
        $port = (string) ($cfg['port'] ?? 3306);

        if (! $database || ! $username) {
            $this->error('MySQL database/username not configured.');

            return self::FAILURE;
        }

        $isWeekly = (bool) $this->option('weekly');
        $backupPath = storage_path('app/backups/'.($isWeekly ? 'weekly' : 'daily'));
        File::ensureDirectoryExists($backupPath, 0750);

        $fileName = 'backup-'.now()->format('Y-m-d_H-i-s').'.sql.gz';
        $filePath = $backupPath.'/'.$fileName;

        // Args array — no shell interpolation of password
        $process = new Process([
            'mysqldump',
            '--user='.$username,
            '--host='.$host,
            '--port='.$port,
            '--single-transaction',
            '--quick',
            '--routines',
            '--triggers',
            $database,
        ]);
        $process->setTimeout(1800);
        // Merge — setEnv replaces whole env (would drop PATH)
        $env = array_merge($_ENV, $_SERVER);
        if ($password !== '') {
            $env['MYSQL_PWD'] = $password;
        }
        $process->setEnv($env);

        $this->info('Starting database backup...');

        try {
            $process->mustRun();
            $sql = $process->getOutput();
            if ($sql === '') {
                throw new \RuntimeException('mysqldump produced empty output.');
            }

            $gz = gzencode($sql, 6);
            if ($gz === false) {
                throw new \RuntimeException('gzip failed.');
            }
            File::put($filePath, $gz);
            @chmod($filePath, 0640);

            $keep = $isWeekly ? 4 : 7;
            $this->cleanupOldBackups($backupPath, $keep);

            $bytes = File::size($filePath);
            $this->info('Database backup OK: '.$filePath.' ('.number_format($bytes / 1024, 1).' KB)');
            Log::channel('backup')->info('db:backup ok', [
                'path' => $filePath,
                'bytes' => $bytes,
                'weekly' => $isWeekly,
            ]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Backup failed: '.$e->getMessage());
            Log::channel('backup')->error('db:backup failed', ['message' => $e->getMessage()]);

            return self::FAILURE;
        }
    }

    protected function cleanupOldBackups(string $backupPath, int $keep = 7): void
    {
        $files = collect(File::files($backupPath))
            ->filter(fn ($f) => str_contains($f->getFilename(), 'backup-'))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->values();

        foreach ($files->slice($keep) as $old) {
            File::delete($old->getPathname());
            $this->line('Deleted old backup: '.$old->getFilename());
        }
    }
}
