<?php

namespace App\Services\Integration;

use App\Repositories\Identity\Contracts\OpdRepositoryInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class OpdSyncService
{
    public function __construct(
        private readonly OpdRepositoryInterface $opds,
    ) {}

    /** @return array{created:int,updated:int,restored:int,deactivated:int,message:string} */
    public function sync(): array
    {
        $rows = $this->fetch();
        $created = $updated = $restored = 0;
        $seen = [];

        foreach ($rows as $row) {
            $code = trim((string) ($row[config('services.kedaton.opd_code_field')] ?? $row['kode_uker'] ?? ''));
            $name = trim((string) ($row[config('services.kedaton.opd_name_field')] ?? $row['nama_uker'] ?? $row['uker'] ?? ''));
            if ($code === '' || $name === '' || in_array($code, $seen, true)) continue;
            $seen[] = $code;
            $opd = $this->opds->findByCodeIncludingTrashed($code);
            if ($opd) {
                $wasTrashed = $opd->trashed();
                if ($wasTrashed) { $this->opds->restore($opd); $restored++; }
                $this->opds->update($opd, ['name' => $name, 'is_active' => true, 'synced_at' => now()]);
                if (! $wasTrashed) $updated++;
            } else {
                $this->opds->create(['code' => $code, 'name' => $name, 'is_active' => true, 'synced_at' => now()]);
                $created++;
            }
        }

        $deactivated = $this->opds->deactivateMissingSynced($seen);

        return compact('created', 'updated', 'restored', 'deactivated') + [
            'message' => "Sinkronisasi OPD berhasil. Ditambahkan: {$created}, diperbarui: {$updated}, dipulihkan: {$restored}, dinonaktifkan: {$deactivated}",
        ];
    }

    /** @return array<int,array<string,mixed>> */
    protected function fetch(): array
    {
        $path = config('services.kedaton.opd_master_path');
        if (! filled($path)) throw new RuntimeException('KEDATON_OPD_MASTER_PATH belum dikonfigurasi.');
        $url = rtrim((string) config('services.kedaton.base_url'), '/') . '/' . ltrim($path, '/');
        $key = config('services.kedaton.data_key');
        if (! filled($key)) throw new RuntimeException('KEDATON_DATA_KEY belum dikonfigurasi.');
        $http = Http::timeout(20)->acceptJson();
        $response = strtolower((string) config('services.kedaton.opd_master_method', 'post')) === 'post'
            ? $http->asForm()->post($url, [
                'key' => $key,
                'nip' => '',
                'nik' => '',
                'uker_kode' => '',
                'tipe_data' => config('services.kedaton.opd_data_type', 'perangkat'),
            ])
            : $http->withToken((string) config('services.kedaton.data_key'))->get($url);
        if (! $response->successful()) {
            Log::warning('Data OPD Kedaton gagal diambil.', ['event' => 'opd.fetch.failed','status' => $response->status()]);
            throw new RuntimeException('Gagal mengambil master OPD dari Kedaton (HTTP '.$response->status().').');
        }
        $json = $response->json();
        if (! is_array($json)) throw new RuntimeException('Respons master OPD Kedaton tidak valid.');
        if (isset($json['message']) && strtoupper((string) $json['message']) !== 'BERHASIL') {
            throw new RuntimeException('Pengambilan struktur OPD ditolak oleh Kedaton.');
        }
        foreach (array_filter(explode('.', (string) config('services.kedaton.opd_response_path', ''))) as $segment) {
            if ($segment === '') continue;
            $json = $json[$segment] ?? null;
            if (! is_array($json)) throw new RuntimeException('Path respons master OPD tidak ditemukan.');
        }
        if (! array_is_list($json)) throw new RuntimeException('Daftar struktur OPD tidak valid.');
        return array_values(array_filter($json, 'is_array'));
    }
}
