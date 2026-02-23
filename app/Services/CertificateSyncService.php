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
    public function syncPull(): array
    {
        // Ambil token
        $token = $this->tokenService->getToken();

        // Panggil API SSO
        $response = Http::withHeaders([
            'apikey' => env('API_KEY'),
            'auth'   => $token,
        ])->asForm()->post(env('SSO_BASE_URL') . '/api/get_data_sertifikat');

        // Jika 401 (token stale), force refresh dan retry
        if ($response->status() === 401) {
            Log::warning('[CertificateSyncService] Token 401 — force refresh dan retry.');
            $token    = $this->tokenService->forceRefresh();
            $response = Http::withHeaders([
                'apikey' => env('API_KEY'),
                'auth'   => $token,
            ])->asForm()->post(env('SSO_BASE_URL') . '/api/get_data_sertifikat');
        }

        if (!$response->successful()) {
            Log::warning('[CertificateSyncService] Gagal ambil data SSO.', ['body' => $response->body()]);
            throw new \Exception('Gagal mengambil data sertifikat dari SSO (HTTP ' . $response->status() . ').');
        }

        $dataKelas = json_decode($response->body(), true);

        if (empty($dataKelas)) {
            return ['updated' => 0, 'message' => 'Tidak ada data sertifikat dari SSO.'];
        }

        // Kelompokkan & filter data (hanya peserta yang sudah punya URL valid)
        $groupedData = [];
        foreach ($dataKelas as $kelas) {
            $key = $kelas['nama_kegiatan'] . '|' . $kelas['tgl_mulai'] . '|' . $kelas['tgl_selesai'];

            if (!isset($groupedData[$key])) {
                $groupedData[$key] = [
                    'nama_kegiatan' => $kelas['nama_kegiatan'],
                    'peserta'       => [],
                ];
            }

            foreach ($kelas['peserta'] as $peserta) {
                $sertifikat = $peserta['sertifikat'];
                if (strpos($sertifikat, 'http') !== false && $sertifikat !== '-') {
                    $groupedData[$key]['peserta'][] = [
                        'nip'        => $peserta['nip'],
                        'sertifikat' => $sertifikat,
                    ];
                }
            }
        }

        $filteredData = array_values($groupedData);

        // Ambil learns yang belum punya url_certificate
        $learns = Learn::with('course')
            ->whereNull('url_certificate')
            ->get();

        $updated = 0;
        foreach ($filteredData as $kelas) {
            foreach ($kelas['peserta'] as $peserta) {
                $learns->each(function ($learn) use ($kelas, $peserta, &$updated) {
                    if (
                        $kelas['nama_kegiatan'] === $learn->course->title &&
                        get_user_meta($learn->participant_id, 'nip') === $peserta['nip']
                    ) {
                        Learn::where('course_id', $learn->course->id)
                            ->where('participant_id', $learn->participant_id)
                            ->update([
                                'url_certificate'     => $peserta['sertifikat'],
                                'is_post_certificate' => 1,
                            ]);

                        $updated++;
                    }
                });
            }
        }

        return [
            'updated' => $updated,
            'message' => "Pull selesai. {$updated} sertifikat berhasil diperbarui.",
        ];
    }
}
