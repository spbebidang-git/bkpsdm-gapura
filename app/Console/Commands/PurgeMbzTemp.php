<?php

namespace App\Console\Commands;

use App\Services\Course\Import\MbzImportService;
use Illuminate\Console\Command;

/**
 * Membuang sisa sesi impor .mbz di storage/app/mbz_imports.
 *
 * Wizard impor menyalin file yang DIUNGGAH ke folder itu dan baru
 * menghapusnya saat impor selesai. Impor yang ditinggalkan di tengah jalan
 * meninggalkan file ratusan MB, jadi folder ini perlu disapu berkala.
 * Berkas .mbz di folder inbox server tidak pernah disentuh perintah ini.
 */
class PurgeMbzTemp extends Command
{
    protected $signature = 'mbz:purge-temp
        {--hours= : Umur minimal berkas yang dibuang (default dari config mbz.temp_ttl_hours)}
        {--dry-run : Tampilkan saja tanpa menghapus}';

    protected $description = 'Hapus sisa sesi impor .mbz yang sudah kedaluwarsa di storage/app/mbz_imports.';

    public function handle(): int
    {
        $hours = (int) ($this->option('hours') ?: config('mbz.temp_ttl_hours', 24));
        $hours = max($hours, 1);
        $dryRun = (bool) $this->option('dry-run');

        $dir = storage_path('app' . DIRECTORY_SEPARATOR . MbzImportService::TMP_DIR);

        if (!is_dir($dir)) {
            $this->info('Folder sesi impor belum ada - tidak ada yang dibersihkan.');

            return self::SUCCESS;
        }

        $cutoff = time() - ($hours * 3600);
        $files = glob($dir . DIRECTORY_SEPARATOR . '*') ?: [];

        $removed = 0;
        $freed = 0;
        $kept = 0;

        foreach ($files as $file) {
            if (!is_file($file)) {
                continue;
            }

            if (filemtime($file) > $cutoff) {
                $kept++;
                continue;
            }

            $size = filesize($file) ?: 0;

            if ($dryRun) {
                $this->line('  akan dihapus: ' . basename($file) . ' (' . $this->humanSize($size) . ')');
                $removed++;
                $freed += $size;
                continue;
            }

            if (@unlink($file)) {
                $this->line('  dihapus: ' . basename($file) . ' (' . $this->humanSize($size) . ')');
                $removed++;
                $freed += $size;
                continue;
            }

            $this->warn('  gagal menghapus: ' . basename($file));
        }

        $this->info(sprintf(
            '%s %d berkas (%s), %d masih aktif (< %d jam).',
            $dryRun ? 'Akan membuang' : 'Dibuang',
            $removed,
            $this->humanSize($freed),
            $kept,
            $hours
        ));

        return self::SUCCESS;
    }

    protected function humanSize(int $bytes): string
    {
        return $bytes >= 1073741824
            ? round($bytes / 1073741824, 1) . ' GB'
            : round($bytes / 1048576) . ' MB';
    }
}
