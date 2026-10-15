<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class BackupHealth extends Command
{
    protected $signature = 'backup:health';

    protected $description = 'Fail and alert when backup prerequisites or verified backups are missing or stale';

    public function handle(BackupService $backups): int
    {
        $problems = [];
        try {
            $backups->checkConfiguration();
        } catch (\Throwable $e) {
            $problems[] = 'Backup prerequisites are not ready (age, recipient or off-site storage).';
        }
        if (app()->environment('production') && (! config('backup.alert_email') || in_array(config('mail.default'), ['log', 'array'], true))) {
            $problems[] = 'Production backup alerts require an email recipient and a real mail transport.';
        }
        foreach (['logs' => 30, 'daily' => 30, 'files' => 30, 'weekly' => 192] as $kind => $hours) {
            try {
                $dir = config('backup.root').'/'.$kind;
                $manifest = is_dir($dir) ? collect(File::files($dir))
                    ->filter(fn ($file) => ! $file->isLink() && str_ends_with($file->getFilename(), '.age.json'))
                    ->sortByDesc(fn ($file) => $file->getFilename())->first() : null;
                if (! $manifest) {
                    throw new \RuntimeException;
                }
                $meta = json_decode(File::get($manifest->getPathname()), true, flags: JSON_THROW_ON_ERROR);
                $path = substr($manifest->getPathname(), 0, -5);
                $created = CarbonImmutable::parse($meta['created_at']);
                if ($meta['kind'] !== $kind || $meta['name'] !== basename($path)
                    || $created->isFuture() || $created->lt(now()->subHours($hours))
                    || is_link($path) || ! is_file($path) || filesize($path) !== $meta['bytes']
                    || $meta['remote_disk'] !== config('backup.remote_disk')) {
                    throw new \RuntimeException;
                }
                if ($meta['remote_disk']) {
                    $disk = Storage::disk($meta['remote_disk']);
                    if (! $disk->exists($meta['remote_key'].'.json') || $disk->size($meta['remote_key']) !== $meta['bytes']) {
                        throw new \RuntimeException;
                    }
                }
                $this->info($kind.': verified backup is recent');
            } catch (\Throwable) {
                $problems[] = $kind.': verified backup is missing, stale or unavailable';
            }
        }
        if ($problems !== []) {
            foreach ($problems as $problem) {
                $this->error($problem);
            }
            $backups->alert('Backup health check failed', ['problems' => $problems]);

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
