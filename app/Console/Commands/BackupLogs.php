<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;

/**
 * Daily archive of application log files.
 * Packs storage/logs/*.log into storage/app/backups/logs/logs-YYYY-MM-DD.zip
 * Keeps LOG_BACKUP_KEEP days of archives (default 30).
 */
class BackupLogs extends Command
{
    protected $signature = 'logs:backup';

    protected $description = 'Archive storage/logs into daily zip under storage/app/backups/logs';

    public function handle(): int
    {
        $logsPath = storage_path('logs');
        $backupPath = storage_path('app/backups/logs');
        $keep = (int) env('LOG_BACKUP_KEEP', 30);
        $date = now()->format('Y-m-d');
        $zipPath = $backupPath.'/logs-'.$date.'.zip';

        if (! File::isDirectory($logsPath)) {
            $this->warn('No logs directory.');

            return self::SUCCESS;
        }

        File::ensureDirectoryExists($backupPath, 0750);

        $files = collect(File::files($logsPath))
            ->filter(fn ($f) => str_ends_with(strtolower($f->getFilename()), '.log'))
            ->values();

        if ($files->isEmpty()) {
            $this->info('No .log files to archive.');

            return self::SUCCESS;
        }

        if (File::exists($zipPath)) {
            File::delete($zipPath);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error('Cannot create zip: '.$zipPath);
            Log::channel('backup')->error('logs:backup zip open failed', ['path' => $zipPath]);

            return self::FAILURE;
        }

        foreach ($files as $file) {
            $zip->addFile($file->getPathname(), $file->getFilename());
        }
        $zip->close();

        @chmod($zipPath, 0640);

        $this->cleanupOld($backupPath, $keep);

        $size = File::size($zipPath);
        $this->info("Logs archived: {$zipPath} (".number_format($size / 1024, 1).' KB)');
        Log::channel('backup')->info('logs:backup ok', [
            'path' => $zipPath,
            'bytes' => $size,
            'files' => $files->count(),
        ]);

        return self::SUCCESS;
    }

    protected function cleanupOld(string $backupPath, int $keep): void
    {
        $files = collect(File::files($backupPath))
            ->filter(fn ($f) => str_ends_with($f->getFilename(), '.zip'))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->values();

        foreach ($files->slice($keep) as $old) {
            File::delete($old->getPathname());
            $this->line('Deleted old log backup: '.$old->getFilename());
        }
    }
}
