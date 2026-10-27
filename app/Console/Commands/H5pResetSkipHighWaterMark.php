<?php

namespace App\Console\Commands;

use App\Models\Course\H5p;
use App\Models\Learn\ActivityRuntimeState;
use App\Services\Course\Activity\H5pService;
use Illuminate\Console\Command;

/**
 * Mencabut "izin melompat maju" yang tertinggal di state peserta.
 *
 * h5p:disable-forward-skip hanya memperbaiki paketnya. State yang sudah
 * tersimpan sebelum aturan itu berlaku masih membawa maxTimeReached jauh di
 * depan posisi tonton, dan preventSkippingMode=forward mengizinkan lompatan
 * sampai titik tersebut. Perintah ini menurunkannya kembali ke progress.
 */
class H5pResetSkipHighWaterMark extends Command
{
    protected $signature = 'h5p:reset-skip-highwater {--dry-run : Tampilkan rencana perubahan tanpa menyimpan.}';

    protected $description = 'Turunkan maxTimeReached ke posisi tonton pada state H5P InteractiveVideo peserta.';

    public function handle(H5pService $h5p): int
    {
        $states = ActivityRuntimeState::with('activity')
            ->whereIn('activity_id', H5p::query()->select('activity_id'))
            ->get();

        if ($states->isEmpty()) {
            $this->info('Tidak ada state H5P peserta yang tersimpan.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $changed = 0;

        foreach ($states as $state) {
            $title = $state->activity->title ?? ('activity #' . $state->activity_id);
            $label = "learn #{$state->learn_id} - {$title}";
            $changes = $h5p->clampRuntimeSkipHighWaterMark($state);

            if ($changes === []) {
                $this->line("  dilewati: {$label}");
                continue;
            }

            if (!$dryRun) {
                $state->save();
            }

            $changed++;

            foreach ($changes as $change) {
                $this->line(sprintf(
                    '  diubah  : %s (maxTimeReached %.2f -> %.2f)',
                    $label,
                    $change['from'],
                    $change['to'],
                ));
            }
        }

        $this->info(sprintf(
            '%s %d dari %d state diubah.',
            $dryRun ? 'Uji coba (tanpa simpan).' : 'Selesai.',
            $changed,
            $states->count(),
        ));

        return self::SUCCESS;
    }
}
