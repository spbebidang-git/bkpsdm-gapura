<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Learn\Learn;
use App\Services\TokenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PostCertificateCommand extends Command
{
    protected $signature = 'app:post-certificate-command {--course= : Filter by specific course ID} {--learn= : Filter by specific learn ID}';

    protected $description = 'Mengirim data sertifikat peserta dari pelatihan yang selesai ke SSO (chunk per batch).';

    /** Max NIP per request ke SSO — hindari 500 body/timeout. */
    protected int $chunkSize = 50;

    protected TokenService $tokenService;

    public function handle(TokenService $tokenService): int
    {
        $this->tokenService = $tokenService;
        try {
            $this->info('[PostCertificate] Mulai proses push sertifikat.');
            Log::info('[PostCertificate] Mulai proses push sertifikat.');

            $token = $tokenService->getToken();

            $this->info('[PostCertificate] Token berhasil diambil.');
            Log::info('[PostCertificate] Token berhasil diambil.');

            $query = Learn::with('course.category')
                ->where('is_post_certificate', 0);

            $courseId = $this->option('course');
            $learnId  = $this->option('learn');

            if ($learnId) {
                // Force re-push 1 learn: abaikan flag
                $query = Learn::with('course.category')
                    ->where('id', $learnId);
                $this->info('[PostCertificate] Filter aktif: learn_id = ' . $learnId);
            } elseif ($courseId) {
                $query->where('course_id', $courseId);
                $this->info('[PostCertificate] Filter aktif: course_id = ' . $courseId);
            }

            $allLearns = $query->get();

            $this->info('[PostCertificate] Data learns ditemukan: ' . $allLearns->count() . ' record yang belum dikirim.');
            Log::info('[PostCertificate] Data learns ditemukan: ' . $allLearns->count() . ' record yang belum dikirim.');

            if ($allLearns->isEmpty()) {
                Log::info('Tidak ada course yang selesai hari ini.');
                $this->info('Tidak ada data yang perlu di-push.');
                return Command::SUCCESS;
            }

            $groupedByCourse = $allLearns->groupBy('course_id');

            $this->info('[PostCertificate] Jumlah course unik: ' . $groupedByCourse->count() . ' | chunk size: ' . $this->chunkSize);
            Log::info('[PostCertificate] Jumlah course unik: ' . $groupedByCourse->count(), [
                'chunk_size' => $this->chunkSize,
            ]);

            $successCount = 0;
            $failCount = 0;
            $pushedParticipants = 0;

            foreach ($groupedByCourse as $courseIdKey => $learnRecords) {
                $courseItem = $learnRecords->first();

                if (!$courseItem->course) {
                    $failCount++;
                    $this->error("Course ID {$courseIdKey} tidak ditemukan di tabel courses, skip.");
                    Log::error("[PostCertificate] Course ID {$courseIdKey} tidak ditemukan.");
                    continue;
                }

                // learn_id + nip; 1 peserta 1 baris (unique participant)
                $entries = [];
                $seenParticipants = [];

                foreach ($learnRecords as $learn) {
                    if (isset($seenParticipants[$learn->participant_id])) {
                        continue;
                    }

                    $nip = get_user_meta($learn->participant_id, 'nip');
                    if (!$nip) {
                        $this->warn("Peserta ID {$learn->participant_id} tidak punya NIP, dilewati.");
                        Log::warning("[PostCertificate] Peserta ID {$learn->participant_id} tidak punya NIP.");
                        continue;
                    }

                    $seenParticipants[$learn->participant_id] = true;
                    $entries[] = [
                        'learn_id' => $learn->id,
                        'participant_id' => $learn->participant_id,
                        'nip' => $nip,
                    ];
                }

                if (empty($entries)) {
                    $failCount++;
                    $this->error('Course "' . $courseItem->course->title . '" tidak punya peserta dengan NIP valid.');
                    Log::error('[PostCertificate] Course "' . $courseItem->course->title . '" tidak punya peserta dengan NIP.');
                    continue;
                }

                $chunks = array_chunk($entries, $this->chunkSize);
                $totalChunks = count($chunks);

                $this->info('[PostCertificate] Course "' . $courseItem->course->title . '": ' . count($entries) . ' peserta → ' . $totalChunks . ' batch.');

                foreach ($chunks as $batchIndex => $chunk) {
                    $batchNo = $batchIndex + 1;
                    $listNip = array_column($chunk, 'nip');
                    $learnIds = array_column($chunk, 'learn_id');

                    try {
                        $response = $this->ssoPostSertifikat($courseItem, $listNip, $token);

                        if (!$response->successful()) {
                            $failCount++;
                            $errorMsg = "Gagal push batch {$batchNo}/{$totalChunks} course \"{$courseItem->course->title}\" | HTTP {$response->status()} | Response: {$response->body()}";
                            Log::error('[PostCertificate] ' . $errorMsg, [
                                'course_id' => $courseIdKey,
                                'batch' => $batchNo,
                                'peserta' => $listNip,
                            ]);
                            $this->error($errorMsg);
                            // batch gagal: flag tetap 0 → sync ulang ambil sisa
                            sleep(2);
                            continue;
                        }

                        // Hanya learn di batch ini
                        Learn::whereIn('id', $learnIds)
                            ->update(['is_post_certificate' => 1]);

                        $successCount++;
                        $pushedParticipants += count($listNip);

                        $successMsg = "Berhasil push batch {$batchNo}/{$totalChunks} course \"{$courseItem->course->title}\" (" . count($listNip) . ' peserta) | HTTP ' . $response->status();
                        Log::info('[PostCertificate] ' . $successMsg, [
                            'course_id' => $courseIdKey,
                            'learn_ids' => $learnIds,
                        ]);
                        $this->info($successMsg);

                        sleep(2);
                    } catch (\Throwable $th) {
                        $failCount++;
                        $errorMsg = "Exception batch {$batchNo}/{$totalChunks} course {$courseIdKey} (\"{$courseItem->course->title}\"): " . $th->getMessage();
                        Log::error('[PostCertificate] ' . $errorMsg, [
                            'trace' => $th->getTraceAsString(),
                        ]);
                        $this->error($errorMsg);
                        sleep(2);
                    }
                }
            }

            $this->info("Push selesai. Berhasil: {$successCount}, Gagal: {$failCount}, Peserta terkirim: {$pushedParticipants}");
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
            'fc_kduker' => 0,
            'fc_kdsatker' => 0,
            'fc_kdsubsatker' => 0,
            'lama_diklat' => $courseItem->course->jam_pelajaran,
            'jenis_waktu' => 'J',
            'kode_kegiatan' => $courseItem->course->category->code,
            'nama_kegiatan' => $courseItem->course->title,
            'tempat_kegiatan' => 'Dinas Komunikasi & Informatika',
            'penyelenggara' => 'BKPSDM Gresik',
            'tahun' => Carbon::now()->year,
            'tgl_mulai' => now()->format('Y-m-d'),
            'tgl_selesai' => now()->format('Y-m-d'),
            'peserta' => implode(',', $listNip),
        ];

        // Jika testing di local, catat di log saja dan buat simulasi sukses
        if (app()->environment('local')) {
            Log::info('--- SIMULASI PUSH LOKAL (Tidak dkirim ke SSO pihak ke 3) ---', [
                'nama_kegiatan' => $courseItem->course->title,
                'peserta' => implode(',', $listNip),
                'payload' => $payload,
            ]);

            Http::fake([
                config('services.sso.base_url') . '/api/post_sertifikat' => Http::response(['message' => 'Simulasi sukses di local'], 200),
            ]);
        }

        $response = Http::withHeaders([
            'apikey' => config('services.sso.api_key'),
            'Authorization' => $token,
        ])
            ->acceptJson()
            ->asForm()
            ->post(config('services.sso.base_url') . '/api/post_sertifikat', $payload);

        Log::info('[PostCertificate] SSO request detail.', [
            'course' => $courseItem->course->title,
            'jml_peserta' => count($listNip),
            'http_status' => $response->status(),
            'response' => $response->body(),
            'payload' => $payload,
        ]);

        if ($response->status() === 401) {
            Log::warning('[PostCertificate] Token 401 — melakukan force refresh dan retry.', [
                'course' => $courseItem->course->title,
            ]);

            $token = $this->tokenService->forceRefresh();

            $response = Http::retry(3, 2000, function ($exception) {
                return $exception instanceof \Illuminate\Http\Client\ConnectionException
                    || ($exception instanceof \Illuminate\Http\Client\RequestException && $exception->response->serverError());
            }, throw: false)
                ->withHeaders([
                    'apikey' => config('services.sso.api_key'),
                    'Authorization' => $token,
                ])->acceptJson()->asForm()->post(config('services.sso.base_url') . '/api/post_sertifikat', $payload);

            Log::info('[PostCertificate] SSO retry setelah force refresh.', [
                'http_status' => $response->status(),
                'response' => $response->body(),
            ]);
        }

        return $response;
    }
}
