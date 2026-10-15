<?php

namespace App\Services\Integration;

use App\Repositories\Learning\Contracts\LearnRepositoryInterface;
use App\Services\Auth\TokenService;
use App\Services\Learning\LearnLifecycleService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CertificateSyncService
{
    public function __construct(
        private readonly TokenService $tokenService,
        private readonly LearnRepositoryInterface $learns,
    ) {}

    /**
     * Lakukan sync pull: ambil URL sertifikat dari SSO lalu update DB.
     *
     * @return array{updated: int, message: string}
     *
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
            $learnsMock = $this->learns->getForCertificateSync($courseId, $learnId);
            $grouped = $learnsMock->groupBy(function ($l) {
                return $l->course?->title ?? 'Unknown';
            });

            foreach ($grouped as $title => $items) {
                $pesertaGroup = [];
                foreach ($items as $item) {
                    $nip = get_user_meta($item->participant_id, 'nip');
                    if ($nip) {
                        $pesertaGroup[] = [
                            'nip' => trim($nip),
                            'sertifikat' => 'https://gapura.test/dummy-cert-view/'.$item->id.'.pdf',
                        ];
                    }
                }
                $mockData[] = [
                    'nama_kegiatan' => $title,
                    'peserta' => $pesertaGroup,
                ];
            }

            Http::fake([
                config('services.sso.base_url').'*' => Http::response($mockData, 200),
            ]);
        }

        $url = rtrim((string) config('services.sso.base_url'), '/').'/api/get_data_sertifikat';

        $response = Http::withHeaders(['Authorization' => $token])
            ->acceptJson()
            ->asForm()
            ->post($url);

        if ($response->status() === 401) {
            Log::warning('[CertificateSyncService] Token 401 — force refresh dan retry.');
            $token = $this->tokenService->forceRefresh();
            $response = Http::withHeaders(['Authorization' => $token])
                ->acceptJson()
                ->asForm()
                ->post($url);
        }

        if (! $response->successful()) {
            Log::warning('[CertificateSyncService] Gagal ambil data SSO.', ['body' => $response->body()]);
            throw new \Exception('Gagal mengambil data sertifikat dari SSO (HTTP '.$response->status().').');
        }

        $dataKelas = json_decode($response->body(), true);

        if (empty($dataKelas)) {
            return ['updated' => 0, 'message' => 'Tidak ada data sertifikat dari SSO.'];
        }

        // Key: "Nama Kegiatan|NIP" => Value: "URL Sertifikat"
        $ssoMap = [];
        foreach ($dataKelas as $kelas) {
            $courseName = trim($kelas['nama_kegiatan'] ?? '');
            if ($courseName === '' || empty($kelas['peserta']) || ! is_array($kelas['peserta'])) {
                continue;
            }
            foreach ($kelas['peserta'] as $peserta) {
                $nip = normalize_nip($peserta['nip'] ?? null);
                $url = trim($peserta['sertifikat'] ?? '');

                if ($nip && strpos($url, 'http') !== false && $url !== '-') {
                    $ssoMap[$courseName.'|'.$nip] = $url;
                }
            }
        }

        // Ambil records learn, filter per course/learn jika diberikan
        $learns = $this->learns->getForCertificateSync($courseId, $learnId, $onlyHasUrl);

        $updated = 0;
        foreach ($learns as $learn) {
            $courseTitle = trim($learn->course?->title ?? '');
            if ($courseTitle === '') {
                continue;
            }

            $userNip = normalize_nip(get_user_meta($learn->participant_id, 'nip'));
            if (! $userNip) {
                continue;
            }

            $lookupKey = $courseTitle.'|'.$userNip;

            // Jika ditemukan di SSO data terbaru
            if (isset($ssoMap[$lookupKey])) {
                $newSertifikatUrl = $ssoMap[$lookupKey];

                // Update hanya jika ada perubahan URL
                if ($learn->url_certificate !== $newSertifikatUrl) {
                    if (app(LearnLifecycleService::class)->setCertificate($learn, $newSertifikatUrl)) $updated++;
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
