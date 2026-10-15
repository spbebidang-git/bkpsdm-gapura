<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupService;
use Illuminate\Console\Command;

class BackupLogs extends Command
{
    protected $signature = 'logs:backup';

    protected $description = 'Create and verify an age-encrypted archive of application logs';

    public function handle(BackupService $backups): int
    {
        try {
            $path = $backups->create('logs', fn ($destination) => $backups->archive($destination, [storage_path('logs')]));
            $this->info('Encrypted log archive verified: '.basename($path));

            return self::SUCCESS;
        } catch (\Throwable) {
            $this->error('Log backup failed; inspect backup health and server monitoring.');

            return self::FAILURE;
        }
    }
}
