<?php

namespace App\Services;

use App\Models\Learn\Learn;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CertificateSyncService
{
    protected TokenService $tokenService;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
    }

    /**
     * Lakukan sync pull: ambil URL sertifikat dari SSO lalu update DB.
     *
     * @return array{updated: int, message: string}
     * @throws \Throwable
     */
    public function syncPull(?int $courseId = null, ?int $learnId = null, bool $onlyHasUrl = false): array
    {
        // Ambil token
        $token = $this->tokenService->getToken();

        // Jika testing di local, buat mock data palsu agar status UI berubah 
        if (app()->environment('local')) {
            Log::info('--- SIMULASI PULL LOKAL (Menyuntikkan URL dummy ke data) ---');
            
            $mockData = [];
            $mq = Learn::with('course');
            if ($learnId) $mq->where('id', $learnId);
            elseif ($courseId) $mq->where('course_id', $courseId);
            
            $learnsMock = $mq->get();
            $grouped = $learnsMock->groupBy(function($l) { return $l->course?->title ?? 'Unknown'; });
            
            foreach ($grouped as $title => $items) {
                $pesertaGroup = [];
                foreach ($items as $item) {
                    $nip = get_user_meta($item->participant_id, 'nip');
                    if ($nip) {
                        $pesertaGroup[] = [
                            'nip' => trim($nip),
                            'sertifikat' => 'https://gapura.test/dummy-cert-view/' . $item->id . '.pdf'
                        ];
                    }
                }
                $mockData[] = [
                    'nama_kegiatan' => $title,
                    'peserta' => $pesertaGroup
                ];
            }

            Http::fake([
                config('services.sso.base_url') . '*' => Http::response($mockData, 200)
            ]);
        }

        // Panggil API SSO
        $response = Http::withHeaders([
            'apikey' => config('services.sso.api_key'),
            'Authorization' => $token,
        ])
            ->acceptJson()
            ->asForm()
            ->post(config('services.sso.base_url') . '/api/get_data_sertifikat');

        // Jika 401 (token stale), force refresh dan retry
        if ($response->status() === 401) {
            Log::warning('[CertificateSyncService] Token 401 — force refresh dan retry.');
            $token    = $this->tokenService->forceRefresh();
            $response = Http::withHeaders([
                'apikey' => env('API_KEY'),
                'Authorization' => $token,
            ])->acceptJson()->asForm()->post(env('SSO_BASE_URL') . '/api/get_data_sertifikat');
        }

        if (!$response->successful()) {
            Log::warning('[CertificateSyncService] Gagal ambil data SSO.', ['body' => $response->body()]);
            throw new \Exception('Gagal mengambil data sertifikat dari SSO (HTTP ' . $response->status() . ').');
        }

        $dataKelas = json_decode($response->body(), true);

        if (empty($dataKelas)) {
            return ['updated' => 0, 'message' => 'Tidak ada data sertifikat dari SSO.'];
        }

        // OPTIMASI: Buat Map data dari SSO untuk lookup cepat O(1)
        // Key: "Nama Kegiatan|NIP" => Value: "URL Sertifikat"
        $ssoMap = [];
        foreach ($dataKelas as $kelas) {
            $courseName = trim($kelas['nama_kegiatan']);
            foreach ($kelas['peserta'] as $peserta) {
                $nip = trim($peserta['nip']);
                $url = trim($peserta['sertifikat']);

                if (strpos($url, 'http') !== false && $url !== '-') {
                    $ssoMap[$courseName . '|' . $nip] = $url;
                }
            }
        }

        // Ambil records learn, filter per course/learn jika diberikan
        $learnQuery = Learn::with('course');
        if ($learnId) {
            $learnQuery->where('id', $learnId);
        } elseif ($courseId) {
            $learnQuery->where('course_id', $courseId);
        }

        // Jika diminta khusus update domain data existing
        if ($onlyHasUrl) {
            $learnQuery->whereNotNull('url_certificate')
                       ->where('url_certificate', '!=', '')
                       ->where('url_certificate', '!=', '-');
        }
        
        $learns = $learnQuery->get();

        $updated = 0;
        foreach ($learns as $learn) {
            $courseTitle = trim($learn->course?->title);
            if (!$courseTitle) continue;

            // Dapatkan NIP untuk pencocokan
            $userNip = get_user_meta($learn->participant_id, 'nip');
            if (!$userNip) continue;

            $lookupKey = $courseTitle . '|' . $userNip;

            // Jika ditemukan di SSO data terbaru
            if (isset($ssoMap[$lookupKey])) {
                $newSertifikatUrl = $ssoMap[$lookupKey];

                // Update hanya jika ada perubahan URL
                if ($learn->url_certificate !== $newSertifikatUrl) {
                    $learn->update([
                        'url_certificate'     => $newSertifikatUrl,
                        'is_post_certificate' => 1,
                    ]);
                    $updated++;
                }
            }
            // Jika tidak ditemukan di SSO, record lama akan tetap menggunakan url_certificate yang lama (tidak tersentuh)
        }

        return [
            'updated' => $updated,
            'message' => "Pull selesai. {$updated} sertifikat berhasil disinkronisasi ulang.",
        ];
    }
}
