<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Learn\Learn;
use App\Services\TokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PostCertificateCommand extends Command
{
    protected $signature = 'app:post-certificate-command';

    protected $description = 'Mengirim data sertifikat peserta dari pelatihan yang selesai hari ini.';

    protected TokenService $tokenService;

    public function handle(TokenService $tokenService): int
    {
        $this->tokenService = $tokenService;
        try {
            if (app()->isLocal()) {
                Log::debug('[PostCertificate] Mulai proses push sertifikat.');
            }

            $token = $tokenService->getToken();

            if (app()->isLocal()) {
                Log::debug('[PostCertificate] Token berhasil diambil.');
            }

            $courses = Learn::with('course.category')
                ->whereDate('finished_at', '>=', '2025-08-01')
                ->where('is_post_certificate', 0)
                ->get();

            if (app()->isLocal()) {
                Log::debug('[PostCertificate] Data learns ditemukan: ' . $courses->count() . ' item.');
            }

            if ($courses->isEmpty()) {
                Log::info('Tidak ada course yang selesai hari ini.');
                $this->info('Tidak ada data yang perlu di-push.');
                return Command::SUCCESS;
            }

            $successCount = 0;
            $failCount    = 0;

            foreach ($courses as $courseItem) {
                try {
                    DB::beginTransaction();

                    $participants = Learn::where('course_id', $courseItem->course->id)
                        ->whereDate('finished_at', '>=', '2025-08-01')
                        ->where('is_post_certificate', 0)
                        ->get()
                        ->pluck('participant_id');

                    $listNip = [];
                    foreach ($participants as $participantId) {
                        $listNip[] = get_user_meta($participantId, 'nip');
                    }

                    $response = $this->ssoPostSertifikat($courseItem, $listNip, $token);

                    if (!$response->successful()) {
                        DB::rollBack();
                        $failCount++;

                        Log::warning('Gagal mengirim data sertifikat.', [
                            'course_id' => $courseItem->id,
                            'response'  => $response->body()
                        ]);
                        $this->warn('Gagal push course: ' . ($courseItem->course->title ?? $courseItem->id));
                        continue;
                    }

                    Learn::where('course_id', $courseItem->course->id)
                        ->whereDate('finished_at', '>=', '2025-08-01')
                        ->where('is_post_certificate', 0)
                        ->update(['is_post_certificate' => 1]);

                    DB::commit();
                    $successCount++;

                    Log::info('Sertifikat berhasil diposting.', ['course_id' => $courseItem->id]);
                } catch (\Throwable $th) {
                    DB::rollBack();
                    $failCount++;

                    Log::error('Exception saat posting sertifikat:', [
                        'message'   => $th->getMessage(),
                        'course_id' => $courseItem->id
                    ]);
                    $this->error('Exception pada course ' . $courseItem->id . ': ' . $th->getMessage());
                }
            }

            $this->info("Push selesai. Berhasil: {$successCount}, Gagal: {$failCount}");
            return $failCount > 0 ? Command::FAILURE : Command::SUCCESS;

        } catch (\Throwable $th) {
            Log::error('Exception utama PostCertificate:', ['message' => $th->getMessage()]);
            $this->error('Gagal mengambil token atau koneksi SSO: ' . $th->getMessage());
            return Command::FAILURE;
        }
    }

    /**
     * Kirim request ke SSO. Jika response 401, paksa refresh token dan coba sekali lagi.
     */
    private function ssoPostSertifikat($courseItem, array $listNip, string &$token)
    {
        $payload = [
            'fc_kduker'        => 0,
            'fc_kdsatker'      => 0,
            'fc_kdsubsatker'   => 0,
            'lama_diklat'      => $courseItem->course->jam_pelajaran,
            'jenis_waktu'      => 'J',
            'kode_kegiatan'    => $courseItem->course->category->code,
            'nama_kegiatan'    => $courseItem->course->title,
            'tempat_kegiatan'  => 'Dinas Komunikasi & Informatika',
            'penyelenggara'    => 'BKPSDM Gresik',
            'tahun'            => Carbon::now()->year,
            'tgl_mulai'        => now()->format('Y-m-d'),
            'tgl_selesai'      => now()->format('Y-m-d'),
            'peserta'          => implode(',', $listNip),
        ];

        $response = Http::withHeaders([
            'apikey' => env('API_KEY'),
            'auth'   => $token,
        ])->asForm()->post(env('SSO_BASE_URL') . '/api/post_sertifikat', $payload);

        if (app()->isLocal()) {
            Log::debug('[PostCertificate] SSO request dikirim.', [
                'course'      => $courseItem->course->title,
                'peserta'     => $listNip,
                'http_status' => $response->status(),
                'response'    => $response->body(),
            ]);
        }

        // Jika 401 (token stale di server), force refresh dan coba sekali lagi
        if ($response->status() === 401) {
            Log::warning('[PostCertificate] Token 401 — melakukan force refresh dan retry.', [
                'course' => $courseItem->course->title,
            ]);

            $token    = $this->tokenService->forceRefresh();
            $response = Http::withHeaders([
                'apikey' => env('API_KEY'),
                'auth'   => $token,
            ])->asForm()->post(env('SSO_BASE_URL') . '/api/post_sertifikat', $payload);

            if (app()->isLocal()) {
                Log::debug('[PostCertificate] SSO retry setelah force refresh.', [
                    'http_status' => $response->status(),
                    'response'    => $response->body(),
                ]);
            }
        }

        return $response;
    }
}
