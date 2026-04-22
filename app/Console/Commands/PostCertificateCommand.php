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
    protected $signature = 'app:post-certificate-command {--course= : Filter by specific course ID} {--learn= : Filter by specific learn ID}';

    protected $description = 'Mengirim data sertifikat peserta dari pelatihan yang selesai hari ini.';

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

            // Ambil semua Learn yang belum di-push, lalu group berdasarkan course_id
            // agar setiap course hanya dikirim SATU KALI ke SSO
            $query = Learn::with('course.category')
                ->where('is_post_certificate', 0);

            $courseId = $this->option('course');
            $learnId  = $this->option('learn');

            if ($learnId) {
                // If filtering by learnId, ignore the is_post_certificate check to allow forced re-push
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

            // Group by course_id supaya 1 course = 1 request ke SSO
            $groupedByCourse = $allLearns->groupBy('course_id');

            $this->info('[PostCertificate] Jumlah course unik: ' . $groupedByCourse->count());
            Log::info('[PostCertificate] Jumlah course unik: ' . $groupedByCourse->count());

            $successCount = 0;
            $failCount = 0;

            foreach ($groupedByCourse as $courseId => $learnRecords) {
                // Ambil salah satu record sebagai representasi course (semua punya relasi course yang sama)
                $courseItem = $learnRecords->first();

                if (!$courseItem->course) {
                    $failCount++;
                    $this->error("Course ID {$courseId} tidak ditemukan di tabel courses, skip.");
                    Log::error("[PostCertificate] Course ID {$courseId} tidak ditemukan.");
                    continue;
                }

                try {
                    DB::beginTransaction();

                    // Kumpulkan NIP dari semua peserta unik di course ini
                    $participantIds = $learnRecords->pluck('participant_id')->unique();
                    $listNip = [];
                    foreach ($participantIds as $participantId) {
                        $nip = get_user_meta($participantId, 'nip');
                        if ($nip) {
                            $listNip[] = $nip;
                        } else {
                            $this->warn("Peserta ID {$participantId} tidak punya NIP, dilewati.");
                            Log::warning("[PostCertificate] Peserta ID {$participantId} tidak punya NIP.");
                        }
                    }

                    if (empty($listNip)) {
                        DB::rollBack();
                        $failCount++;
                        $this->error('Course "' . $courseItem->course->title . '" tidak punya peserta dengan NIP valid.');
                        Log::error('[PostCertificate] Course "' . $courseItem->course->title . '" tidak punya peserta dengan NIP.');
                        continue;
                    }

                    $response = $this->ssoPostSertifikat($courseItem, $listNip, $token);

                    if (!$response->successful()) {
                        DB::rollBack();
                        $failCount++;

                        $errorMsg = 'Gagal push course: "' . ($courseItem->course->title ?? $courseId) . '" | HTTP ' . $response->status() . ' | Response: ' . $response->body();
                        Log::error('[PostCertificate] ' . $errorMsg, [
                            'course_id' => $courseId,
                            'peserta' => $listNip
                        ]);
                        $this->error($errorMsg);
                        continue;
                    }

                    // Update semua Learn record untuk course ini
                    Learn::where('course_id', $courseId)
                        ->where('is_post_certificate', 0)
                        ->update(['is_post_certificate' => 1]);

                    DB::commit();
                    $successCount++;

                    $successMsg = 'Berhasil push course: "' . ($courseItem->course->title ?? $courseId) . '" (' . count($listNip) . ' peserta) | HTTP ' . $response->status();
                    Log::info('[PostCertificate] ' . $successMsg, ['course_id' => $courseId]);
                    $this->info($successMsg);

                    // Jeda antar request agar tidak membanjiri SSO server
                    sleep(2);
                } catch (\Throwable $th) {
                    DB::rollBack();
                    $failCount++;

                    $errorMsg = 'Exception pada course ' . $courseId . ' ("' . ($courseItem->course->title ?? '') . '"): ' . $th->getMessage();
                    Log::error('[PostCertificate] ' . $errorMsg, [
                        'trace' => $th->getTraceAsString()
                    ]);
                    $this->error($errorMsg);
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
                'payload' => $payload
            ]);

            // Fake request seolah-olah sukses 200 OK
            Http::fake([
                config('services.sso.base_url') . '/api/post_sertifikat' => Http::response(['message' => 'Simulasi sukses di local'], 200)
            ]);
        }

        // Request pertama TANPA retry — supaya response 401 bisa ditangani manual
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

        // Jika 401 (token stale di server), force refresh dan coba sekali lagi
        if ($response->status() === 401) {
            Log::warning('[PostCertificate] Token 401 — melakukan force refresh dan retry.', [
                'course' => $courseItem->course->title,
            ]);

            $token = $this->tokenService->forceRefresh();

            // Retry dengan token baru + retry untuk antisipasi 5xx
            $response = Http::retry(3, 2000, function ($exception) {
                return $exception instanceof \Illuminate\Http\Client\ConnectionException
                    || ($exception instanceof \Illuminate\Http\Client\RequestException && $exception->response->serverError());
            }, throw: false)
                ->withHeaders([
                    'apikey' => env('API_KEY'),
                    'Authorization' => $token,
                ])->acceptJson()->asForm()->post(env('SSO_BASE_URL') . '/api/post_sertifikat', $payload);

            Log::info('[PostCertificate] SSO retry setelah force refresh.', [
                'http_status' => $response->status(),
                'response' => $response->body(),
            ]);
        }

        return $response;
    }
}
