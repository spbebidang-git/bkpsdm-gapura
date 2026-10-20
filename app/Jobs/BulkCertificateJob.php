<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

class BulkCertificateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // An uncertain write must be reviewed before retrying the job.
    public $tries = 1;

    public function __construct(protected string $courseId) {}

    public function handle(): void
    {
        if (Artisan::call('app:post-certificate-command', ['--course' => $this->courseId]) !== 0) {
            throw new RuntimeException('Pengiriman sertifikat gagal; periksa log sebelum mengirim ulang.');
        }
    }
}
