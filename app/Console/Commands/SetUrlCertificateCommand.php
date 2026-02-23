<?php

namespace App\Console\Commands;

use App\Models\Learn\Learn;
use App\Services\TokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SetUrlCertificateCommand extends Command
{
    protected $signature = 'app:set-url-certificate-command';
    protected $description = 'Fetch and set URL certificates for participants';

    protected TokenService $tokenService;

    public function handle(TokenService $tokenService): int
    {
        $this->tokenService = $tokenService;
        try {
            if (app()->isLocal()) {
                Log::debug('[SetUrlCertificate] Mulai proses pull sertifikat.');
            }

            # Ambil token
            $token = $tokenService->getToken();

            if (app()->isLocal()) {
                Log::debug('[SetUrlCertificate] Token berhasil diambil.');
            }

            # Ambil data sertifikat dari API
            $response = Http::withHeaders([
                'apikey' => env('API_KEY'),
                'auth'   => $token,
            ])
                ->asForm()
                ->post(env('SSO_BASE_URL') . '/api/get_data_sertifikat');

            // Jika 401 (token stale), force refresh dan retry
            if ($response->status() === 401) {
                Log::warning('[SetUrlCertificate] Token 401 — force refresh dan retry.');
                $token = $this->tokenService->forceRefresh();
                $response = Http::withHeaders([
                    'apikey' => env('API_KEY'),
                    'auth'   => $token,
                ])->asForm()->post(env('SSO_BASE_URL') . '/api/get_data_sertifikat');
            }

            if (app()->isLocal()) {
                Log::debug('[SetUrlCertificate] SSO response.', [
                    'http_status' => $response->status(),
                    'body'        => $response->body(),
                ]);
            }

            if (!$response->successful()) {
                $this->error('Gagal mengambil data sertifikat dari SSO: ' . $response->body());
                Log::warning('SetUrlCertificate: gagal ambil data SSO.', ['body' => $response->body()]);
                return Command::FAILURE;
            }

            $dataKelas = json_decode($response->body(), true);

            if (empty($dataKelas)) {
                $this->info('Tidak ada data sertifikat dari SSO.');
                return Command::SUCCESS;
            }

            # Filter dan kelompokkan data
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
                            'nip'         => $peserta['nip'],
                            'sertifikat'  => $sertifikat,
                        ];
                    }
                }
            }

            $filteredData = array_values($groupedData);

            if (app()->isLocal()) {
                Log::debug('[SetUrlCertificate] Data dari SSO setelah filter.', [
                    'total_kegiatan'          => count($filteredData),
                    'total_peserta_bersertifikat' => array_sum(array_map(fn($k) => count($k['peserta']), $filteredData)),
                ]);
            }

            # Ambil data learn yang belum punya URL sertifikat
            $learns = Learn::with('course')
                ->whereNull('url_certificate')
                ->get();

            if (app()->isLocal()) {
                Log::debug('[SetUrlCertificate] Learns belum punya URL: ' . $learns->count() . ' item.');
            }

            $dispatched = 0;
            foreach ($filteredData as $kelas) {
                foreach ($kelas['peserta'] as $peserta) {
                    $learns->each(function ($learn) use ($kelas, $peserta, &$dispatched) {
                        if (
                            $kelas['nama_kegiatan'] === $learn->course->title &&
                            get_user_meta($learn->participant_id, 'nip') === $peserta['nip']
                        ) {
                            if (app()->isLocal()) {
                                Log::debug('[SetUrlCertificate] Match ditemukan, dispatch job.', [
                                    'course'     => $learn->course->title,
                                    'participant' => $learn->participant_id,
                                    'nip'        => $peserta['nip'],
                                    'url'        => $peserta['sertifikat'],
                                ]);
                            }

                            Learn::where('course_id', $learn->course->id)
                                ->where('participant_id', $learn->participant_id)
                                ->update([
                                    'url_certificate'    => $peserta['sertifikat'],
                                    'is_post_certificate' => 1,
                                ]);

                            $dispatched++;
                        }
                    });
                }
            }

            $this->info("Pull selesai. {$dispatched} sertifikat di-dispatch untuk diperbarui.");
            return Command::SUCCESS;

        } catch (\Throwable $th) {
            Log::error('Exception utama SetUrlCertificate:', ['message' => $th->getMessage()]);
            $this->error('Gagal: ' . $th->getMessage());
            return Command::FAILURE;
        }
    }
}
