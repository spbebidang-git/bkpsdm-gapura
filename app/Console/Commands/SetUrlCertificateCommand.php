<?php

namespace App\Console\Commands;

use App\Services\Integration\CertificateSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SetUrlCertificateCommand extends Command
{
    protected $signature = 'app:set-url-certificate-command';

    protected $description = 'Fetch and set URL certificates for participants';

    public function handle(CertificateSyncService $service): int
    {
        try {
            $this->info($service->syncPull(onlyMissing: true)['message']);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('Pengambilan sertifikat gagal.', ['event' => 'certificate.pull.failed', 'exception' => $e]);
            $this->error('Pengambilan sertifikat gagal. Periksa log menggunakan ID proses.');

            return self::FAILURE;
        }
    }
}
