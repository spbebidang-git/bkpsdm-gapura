<?php

namespace App\Console\Commands;

use App\Jobs\SetUrlCertificateJob;
use App\Models\Learn\Learn;
use App\Services\TokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SetUrlCertificateCommand extends Command
{
    protected $signature = 'app:set-url-certificate-command';
    protected $description = 'Fetch and set URL certificates for participants';

    public function handle(TokenService $tokenService): void
    {
        # Ambil token
        $token = $tokenService->getToken();

        # Ambil data sertifikat dari API
        $response = Http::withHeaders([
            'apikey' => env('API_KEY'),
            'auth'   => $token,
        ])
            ->asForm()
            ->post(env('SSO_BASE_URL') . '/api/get_data_sertifikat')
            ->throw();

        $dataKelas = json_decode($response->body(), true);

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

        # Ubah ke array numerik
        $filteredData = array_values($groupedData);
        # Ambil data learn yang belum punya URL sertifikat
        $learns = Learn::with('course')
            ->whereNull('url_certificate')
            ->get();

        foreach ($filteredData as $kelas) {
            foreach ($kelas['peserta'] as $peserta) {
                $learns->each(function ($learn) use ($kelas, $peserta) {
                    if (
                        $kelas['nama_kegiatan'] === $learn->course->title &&
                        get_user_meta($learn->participant_id, 'nip') === $peserta['nip']
                    ) {
                        SetUrlCertificateJob::dispatch([
                            'course'         => $learn->course->id,
                            'participant'    => $learn->participant_id,
                            'url_certificate' => $peserta['sertifikat'],
                        ]);
                    }
                });
            }
        }
    }
}
