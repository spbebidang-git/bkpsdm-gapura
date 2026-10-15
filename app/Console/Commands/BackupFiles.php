<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupService;
use Illuminate\Console\Command;

class BackupFiles extends Command
{
    protected $signature = 'files:backup';

    protected $description = 'Create and verify an age-encrypted archive of uploaded application files';

    public function handle(BackupService $backups): int
    {
        try {
            $path = $backups->create('files', fn ($destination) => $backups->archive($destination, config('backup.file_roots')));
            $this->info('Encrypted file backup verified: '.basename($path));

            return self::SUCCESS;
        } catch (\Throwable) {
            $this->error('File backup failed; inspect backup health and server monitoring.');

            return self::FAILURE;
        }
    }
}
