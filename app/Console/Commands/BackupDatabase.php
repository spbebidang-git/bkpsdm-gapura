<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupService;
use Illuminate\Console\Command;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup {--weekly : Weekly retention set}';

    protected $description = 'Create and verify an age-encrypted MySQL backup';

    public function handle(BackupService $backups): int
    {
        try {
            $path = $backups->create($this->option('weekly') ? 'weekly' : 'daily', $backups->dumpDatabase(...));
            $this->info('Encrypted database backup verified: '.basename($path));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Database backup failed; inspect backup health and server monitoring.');

            return self::FAILURE;
        }
    }
}
