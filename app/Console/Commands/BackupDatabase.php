<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup {--weekly : Whether this is a weekly backup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup the database using mysqldump';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $database = config('database.connections.mysql.database');
        $username = config('database.connections.mysql.username');
        $password = config('database.connections.mysql.password');
        $host = config('database.connections.mysql.host');
        $port = config('database.connections.mysql.port');

        $isWeekly = $this->option('weekly');
        $backupPath = storage_path('app/backups' . ($isWeekly ? '/weekly' : '/daily'));

        if (!File::exists($backupPath)) {
            File::makeDirectory($backupPath, 0755, true);
        }

        $fileName = 'backup-' . date('Y-m-d_H-i-s') . '.sql';
        $filePath = $backupPath . '/' . $fileName;

        $passwordArg = empty($password) ? '' : "--password={$password}";
        
        $command = "mysqldump --user={$username} {$passwordArg} --host={$host} --port={$port} {$database} > {$filePath}";

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(1800); 

        $this->info('Starting database backup...');

        try {
            $process->mustRun();
            
            $this->info('Database backup was successful. Saved to: ' . $filePath);

            // Keep 7 daily backups, and 4 weekly backups
            $keep = $isWeekly ? 4 : 7;
            $this->cleanupOldBackups($backupPath, $keep);

        } catch (ProcessFailedException $exception) {
            $this->error('The backup process failed.');
            $this->error($exception->getMessage());
        }
    }

    protected function cleanupOldBackups($backupPath, $keep = 7)
    {
        $files = File::files($backupPath);

        if (count($files) > $keep) {
            // Sort files by modified time (oldest first)
            usort($files, function ($a, $b) {
                return $a->getMTime() - $b->getMTime();
            });

            // Keep the newest $keep files, delete the rest
            $filesToDelete = array_slice($files, 0, count($files) - $keep);

            foreach ($filesToDelete as $file) {
                File::delete($file->getPathname());
                $this->line("Deleted old backup: " . $file->getFilename());
            }
        }
    }
}
