<?php

namespace App\Console\Commands;

use App\Models\Course\H5p;
use App\Services\Course\Activity\H5pService;
use Illuminate\Console\Command;

/**
 * Menyeragamkan aturan navigasi paket H5P InteractiveVideo yang sudah terlanjur
 * diekstrak: peserta boleh mengulang mundur, tetapi tidak boleh melompat maju.
 * Paket yang diunggah/diimpor setelah ini sudah ditangani H5pService saat ekstrak.
 */
class H5pDisableForwardSkip extends Command
{
    protected $signature = 'h5p:disable-forward-skip';

    protected $description = 'Set preventSkippingMode=forward pada semua paket H5P InteractiveVideo yang sudah ada.';

    public function handle(H5pService $h5p): int
    {
        $packages = H5p::with('activity')->whereNotNull('folder')->where('folder', '!=', '')->get();

        if ($packages->isEmpty()) {
            $this->info('Tidak ada paket H5P yang terpasang.');

            return self::SUCCESS;
        }

        $changed = 0;

        foreach ($packages as $package) {
            $title = $package->activity->title ?? ('activity #' . $package->activity_id);

            if ($h5p->enforceNoForwardSkipping($package->folder)) {
                $changed++;
                $this->line("  diubah  : {$package->folder} — {$title}");
                continue;
            }

            $this->line("  dilewati: {$package->folder} — {$title}");
        }

        $this->info("Selesai. {$changed} dari {$packages->count()} paket diubah.");

        return self::SUCCESS;
    }
}
