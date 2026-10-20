<?php

namespace App\Console\Commands;

use App\Services\Integration\OpdSyncService;
use Illuminate\Console\Command;

class SyncOpds extends Command
{
    protected $signature = 'kedaton:sync-opds';
    protected $description = 'Sinkronisasi master OPD dari Kedaton';

    public function handle(OpdSyncService $sync): int
    {
        try { $this->info($sync->sync()['message']); return self::SUCCESS; }
        catch (\Throwable $e) { report($e); $this->error('Sinkronisasi OPD gagal. Periksa log menggunakan ID proses.'); return self::FAILURE; }
    }
}
