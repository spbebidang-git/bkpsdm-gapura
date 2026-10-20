<?php

namespace App\Services\Integration;

use App\Repositories\Course\Contracts\CategoryRepositoryInterface;
use App\Repositories\System\Contracts\SettingRepositoryInterface;
use App\Services\Auth\TokenService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CategorySyncService
{
    public function __construct(
        private readonly TokenService $tokenService,
        private readonly CategoryRepositoryInterface $categories,
        private readonly SettingRepositoryInterface $settings,
    ) {}

    /**
     * @return array{created: int, updated: int, soft_deleted: int, restored: int, message: string}
     */
    public function sync(): array
    {
        $rows = $this->fetchFromSso();

        $created = 0;
        $updated = 0;
        $restored = 0;
        $seenCodes = [];

        foreach ($rows as $row) {
            // SSO /api/get_kode_kegiatan → { kode, kegiatan }
            $code = trim((string) ($row['kode'] ?? $row['code'] ?? $row['kode_kegiatan'] ?? ''));
            $title = trim((string) ($row['kegiatan'] ?? $row['title'] ?? $row['nama'] ?? $row['name'] ?? ''));

            if ($code === '' || $title === '') {
                continue;
            }

            $seenCodes[] = $code;

            $category = $this->categories->findByCode($code, true);

            if ($category) {
                $wasTrashed = $category->trashed();
                if ($wasTrashed) {
                    $this->categories->restore($category);
                    $restored++;
                }
                $this->categories->update($category, [
                    'title' => $title,
                    'synced_at' => now(),
                ]);
                if (! $wasTrashed) {
                    $updated++;
                }
            } else {
                $this->categories->create([
                    'code' => $code,
                    'title' => $title,
                    'synced_at' => now(),
                ]);
                $created++;
            }
        }

        $softDeleted = 0;
        if (count($seenCodes) > 0) {
            $softDeleted = $this->categories->softDeleteMissingCodes($seenCodes);
        }

        $setting = $this->settings->findByKey('categories_last_synced_at', true);
        if ($setting) {
            if ($setting->trashed()) {
                $setting = $this->settings->restore($setting);
            }
            $this->settings->update($setting, ['value' => now()->toDateTimeString()]);
        } else {
            $this->settings->create([
                'key' => 'categories_last_synced_at',
                'value' => now()->toDateTimeString(),
            ]);
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'soft_deleted' => $softDeleted,
            'restored' => $restored,
            'message' => "Sinkronisasi kategori berhasil. Ditambahkan: {$created}, diperbarui: {$updated}, dipulihkan: {$restored}, dihapus: {$softDeleted}",
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function fetchFromSso(): array
    {
        // SSO endpoint: /api/get_kode_kegiatan (bukan get_kategori — 404 di prestige)
        $path = config('services.sso.categories_path', '/api/get_kode_kegiatan');
        $token = $this->tokenService->getToken();
        $url = rtrim((string) config('services.sso.base_url'), '/').'/'.ltrim($path, '/');

        $response = Http::timeout(20)
            ->withHeaders(['Authorization' => $token, 'Accept' => 'application/json'])
            ->acceptJson()
            ->post($url);

        if ($response->status() === 401) {
            $token = $this->tokenService->forceRefresh();
            $response = Http::timeout(20)
                ->withHeaders(['Authorization' => $token, 'Accept' => 'application/json'])
                ->acceptJson()
                ->post($url);
        }

        if (! $response->successful()) {
            Log::warning('Data kategori SSO gagal diambil.', ['event' => 'category.fetch.failed',
                'status' => $response->status(),
            ]);
            throw new \RuntimeException('Gagal ambil kategori dari SSO (HTTP '.$response->status().').');
        }

        $json = $response->json();
        if (! is_array($json)) {
            throw new \RuntimeException('Respons kategori SSO tidak valid.');
        }

        if (isset($json['data']) && is_array($json['data'])) {
            $json = $json['data'];
        }

        return array_values(array_filter($json, 'is_array'));
    }
}
