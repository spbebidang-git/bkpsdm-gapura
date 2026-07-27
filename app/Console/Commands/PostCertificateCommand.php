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

    /** @var string[] cuplikan error buat output UI */
    protected array $errorSamples = [];

    /** @var array<string, mixed> ringkasan debug 1 blok (copy-paste) */
    protected array $debug = [];

    public function handle(TokenService $tokenService): int
    {
        $this->tokenService = $tokenService;
        $this->debug = [
            'time' => now()->toDateTimeString(),
            'env' => app()->environment(),
            'sso_base' => config('services.sso.base_url') ?: '(KOSONG)',
            'api_key_set' => config('services.sso.api_key') ? 'yes' : 'NO',
            'client_id_set' => config('services.sso.client_id') ? 'yes' : 'NO',
            'admin_user_set' => config('services.sso.admin_username') ? 'yes' : 'NO',
            'filters' => [
                'course' => $this->option('course'),
                'learn' => $this->option('learn'),
            ],
            'token' => 'pending',
            'courses' => [],
            'http_samples' => [],
            'errors' => [],
        ];

        try {
            $this->info('[PostCertificate] Mulai proses push sertifikat.');
            Log::info('[PostCertificate] Mulai proses push sertifikat.');

            $token = $tokenService->getToken();
            $this->debug['token'] = 'OK (' . strlen($token) . ' chars)';

            $this->info('[PostCertificate] Token berhasil diambil.');
            Log::info('[PostCertificate] Token berhasil diambil.');

            $query = Learn::with(['course.category', 'course.schedules'])
                ->where('is_post_certificate', 0);

            $courseId = $this->option('course');
            $learnId  = $this->option('learn');

            if ($learnId) {
                // Force re-push 1 learn: abaikan flag + finished
                $query = Learn::with(['course.category', 'course.schedules'])
                    ->where('id', $learnId);
                $this->info('[PostCertificate] Filter aktif: learn_id = ' . $learnId);
            } elseif ($courseId) {
                $query->where('course_id', $courseId)
                    ->whereNotNull('finished_at');
                $this->info('[PostCertificate] Filter aktif: course_id = ' . $courseId);
            } else {
                $query->whereNotNull('finished_at');
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

            $pushedParticipants = 0;
            $failedParticipants = 0;

            foreach ($groupedByCourse as $courseIdKey => $learnRecords) {
                $courseItem = $learnRecords->first();

                if (!$courseItem->course) {
                    $failedParticipants += $learnRecords->count();
                    $msg = "Course ID {$courseIdKey} tidak ditemukan di tabel courses, skip.";
                    $this->error($msg);
                    $this->rememberError($msg);
                    Log::error("[PostCertificate] Course ID {$courseIdKey} tidak ditemukan.");
                    continue;
                }

                if (!$courseItem->course->category) {
                    $failedParticipants += $learnRecords->count();
                    $msg = 'Course "' . $courseItem->course->title . '" tidak punya category/kode_kegiatan, skip.';
                    $this->error($msg);
                    $this->rememberError($msg);
                    Log::error('[PostCertificate] Course tanpa category.', ['course_id' => $courseIdKey]);
                    continue;
                }

                $entries = [];
                $seenParticipants = [];

                foreach ($learnRecords as $learn) {
                    if (isset($seenParticipants[$learn->participant_id])) {
                        continue;
                    }

                    $rawNip = get_user_meta($learn->participant_id, 'nip');
                    $nip = normalize_nip($rawNip);
                    if (!$nip) {
                        $failedParticipants++;
                        $this->warn("Peserta ID {$learn->participant_id} NIP invalid/kosong, dilewati.");
                        Log::warning("[PostCertificate] Peserta ID {$learn->participant_id} NIP invalid.", [
                            'raw' => $rawNip,
                        ]);
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
                    $msg = 'Course "' . $courseItem->course->title . '" tidak punya peserta dengan NIP valid.';
                    $this->error($msg);
                    $this->rememberError($msg);
                    Log::error('[PostCertificate] ' . $msg);
                    continue;
                }

                $dates = $this->resolveDates($courseItem, $learnRecords);
                $chunks = array_chunk($entries, $this->chunkSize);
                $totalChunks = count($chunks);

                $courseDebug = [
                    'course_id' => $courseIdKey,
                    'title' => $this->sanitizeTitle($courseItem->course->title ?? ''),
                    'kode_kegiatan' => $courseItem->course->category->code ?? null,
                    'lama_diklat' => $courseItem->course->jam_pelajaran ?? null,
                    'dates' => $dates,
                    'peserta' => count($entries),
                    'batches' => $totalChunks,
                    'nip_sample' => array_slice(array_column($entries, 'nip'), 0, 3),
                    'nip_len_dist' => array_count_values(array_map('strlen', array_column($entries, 'nip'))),
                ];
                $this->debug['courses'][] = $courseDebug;

                $this->info('[PostCertificate] Course "' . $courseItem->course->title . '": ' . count($entries) . ' peserta → ' . $totalChunks . ' batch.');

                foreach ($chunks as $batchIndex => $chunk) {
                    $batchNo = $batchIndex + 1;
                    $listNip = array_column($chunk, 'nip');
                    $learnIds = array_column($chunk, 'learn_id');

                    try {
                        $response = $this->ssoPostSertifikat($courseItem, $listNip, $token, $dates);

                        if ($response->successful()) {
                            Learn::whereIn('id', $learnIds)->update(['is_post_certificate' => 1]);
                            $pushedParticipants += count($listNip);
                            $this->info("Berhasil push batch {$batchNo}/{$totalChunks} (" . count($listNip) . ' peserta) | HTTP ' . $response->status());
                            usleep(300000);
                            continue;
                        }

                        // Batch gagal — isolasi: kirim 1 NIP per request biar 1 busuk tidak gagalkan 49 lainnya
                        $status = $response->status();
                        $body = mb_substr($response->body(), 0, 300);
                        $this->recordHttpSample('batch', $courseIdKey, $batchNo, $status, $body, $listNip, $dates);
                        Log::warning('[PostCertificate] Batch gagal, fallback per-NIP.', [
                            'course_id' => $courseIdKey,
                            'batch' => $batchNo,
                            'http' => $status,
                            'body' => $body,
                            'peserta' => $listNip,
                        ]);
                        $this->warn("Batch {$batchNo}/{$totalChunks} HTTP {$status} — fallback per-NIP. Body: {$body}");
                        $this->rememberError("HTTP {$status}: {$body}");

                        $soloFailStreak = 0;
                        foreach ($chunk as $i => $entry) {
                            try {
                                $solo = $this->ssoPostSertifikat($courseItem, [$entry['nip']], $token, $dates);
                                if ($solo->successful()) {
                                    Learn::where('id', $entry['learn_id'])->update(['is_post_certificate' => 1]);
                                    $pushedParticipants++;
                                    $soloFailStreak = 0;
                                } else {
                                    $failedParticipants++;
                                    $soloFailStreak++;
                                    $soloBody = mb_substr($solo->body(), 0, 200);
                                    Log::error('[PostCertificate] NIP gagal.', [
                                        'nip' => $entry['nip'],
                                        'learn_id' => $entry['learn_id'],
                                        'http' => $solo->status(),
                                        'body' => $soloBody,
                                    ]);
                                    $this->rememberError("NIP {$entry['nip']} HTTP {$solo->status()}: {$soloBody}");
                                    $this->recordHttpSample('solo', $courseIdKey, $batchNo, $solo->status(), $soloBody, [$entry['nip']], $dates);

                                    // 3 NIP beruntun gagal → masalah course/auth, stop sisa course
                                    if ($soloFailStreak >= 3) {
                                        $failedParticipants += max(0, count($chunk) - $i - 1);
                                        $msg = "3 NIP beruntun gagal (HTTP {$solo->status()}) — stop sisa course. Body: {$soloBody}";
                                        $this->error($msg);
                                        $this->rememberError($msg);
                                        Log::error('[PostCertificate] ' . $msg, ['course_id' => $courseIdKey]);
                                        break 2;
                                    }
                                }
                            } catch (\Throwable $th) {
                                $failedParticipants++;
                                $soloFailStreak++;
                                $this->rememberError("NIP {$entry['nip']}: " . $th->getMessage());
                                Log::error('[PostCertificate] Exception per-NIP.', [
                                    'nip' => $entry['nip'],
                                    'message' => $th->getMessage(),
                                ]);
                                if ($soloFailStreak >= 3) {
                                    break 2;
                                }
                            }
                            usleep(150000);
                        }
                    } catch (\Throwable $th) {
                        $failedParticipants += count($chunk);
                        $msg = "Exception batch {$batchNo}/{$totalChunks}: " . $th->getMessage();
                        Log::error('[PostCertificate] ' . $msg);
                        $this->error($msg);
                        $this->rememberError($msg);
                    }
                }
            }

            $hint = $this->errorSamples ? ' | Error: ' . $this->errorSamples[0] : '';
            $this->info("Push selesai. Berhasil: {$pushedParticipants}, Gagal: {$failedParticipants}{$hint}");

            $this->debug['result'] = [
                'berhasil' => $pushedParticipants,
                'gagal' => $failedParticipants,
            ];
            $this->debug['errors'] = $this->errorSamples;
            $this->emitDebugReport();

            return $failedParticipants > 0 && $pushedParticipants === 0
                ? Command::FAILURE
                : Command::SUCCESS;
        } catch (\Throwable $th) {
            $this->debug['token'] = $this->debug['token'] === 'pending'
                ? 'FAIL: ' . $th->getMessage()
                : $this->debug['token'];
            $this->debug['fatal'] = $th->getMessage();
            $this->emitDebugReport();
            Log::error('Exception utama PostCertificate:', ['message' => $th->getMessage()]);
            $this->error('Gagal mengambil token atau koneksi SSO: ' . $th->getMessage());
            return Command::FAILURE;
        }
    }

    private function rememberError(string $msg): void
    {
        if (count($this->errorSamples) >= 3) {
            return;
        }
        $this->errorSamples[] = $msg;
    }

    private function recordHttpSample(string $kind, $courseId, $batchNo, int $status, string $body, array $nips, array $dates): void
    {
        if (count($this->debug['http_samples']) >= 5) {
            return;
        }
        $this->debug['http_samples'][] = [
            'kind' => $kind,
            'course_id' => $courseId,
            'batch' => $batchNo,
            'http' => $status,
            'body' => $body,
            'nip_count' => count($nips),
            'nip_sample' => array_slice($nips, 0, 3),
            'dates' => $dates,
        ];
    }

    /**
     * 1 blok ringkas: log + file + stdout (gampang di-copy).
     */
    private function emitDebugReport(): void
    {
        $lines = [];
        $lines[] = '=== POST_CERT_DEBUG_START ===';
        $lines[] = 'time: ' . ($this->debug['time'] ?? '');
        $lines[] = 'env: ' . ($this->debug['env'] ?? '');
        $lines[] = 'sso_base: ' . ($this->debug['sso_base'] ?? '');
        $lines[] = 'api_key_set: ' . ($this->debug['api_key_set'] ?? '');
        $lines[] = 'client_id_set: ' . ($this->debug['client_id_set'] ?? '');
        $lines[] = 'admin_user_set: ' . ($this->debug['admin_user_set'] ?? '');
        $lines[] = 'token: ' . ($this->debug['token'] ?? '');
        $lines[] = 'filter_course: ' . ($this->debug['filters']['course'] ?? '-');
        $lines[] = 'filter_learn: ' . ($this->debug['filters']['learn'] ?? '-');

        if (!empty($this->debug['fatal'])) {
            $lines[] = 'fatal: ' . $this->debug['fatal'];
        }

        if (!empty($this->debug['result'])) {
            $lines[] = 'berhasil: ' . $this->debug['result']['berhasil'];
            $lines[] = 'gagal: ' . $this->debug['result']['gagal'];
        }

        foreach ($this->debug['courses'] ?? [] as $c) {
            $lines[] = sprintf(
                'course_id=%s | peserta=%s | batches=%s | kode=%s | jp=%s | tgl=%s..%s',
                $c['course_id'],
                $c['peserta'],
                $c['batches'],
                $c['kode_kegiatan'] ?? '-',
                $c['lama_diklat'] ?? '-',
                $c['dates']['tgl_mulai'] ?? '-',
                $c['dates']['tgl_selesai'] ?? '-'
            );
            $lines[] = '  title: ' . ($c['title'] ?? '');
            $lines[] = '  nip_sample: ' . implode(',', $c['nip_sample'] ?? []);
            $lines[] = '  nip_len: ' . json_encode($c['nip_len_dist'] ?? []);
        }

        foreach ($this->debug['http_samples'] ?? [] as $i => $h) {
            $lines[] = sprintf(
                'http[%d] kind=%s course=%s batch=%s status=%s nips=%s',
                $i,
                $h['kind'],
                $h['course_id'],
                $h['batch'],
                $h['http'],
                $h['nip_count']
            );
            $lines[] = '  nip_sample: ' . implode(',', $h['nip_sample'] ?? []);
            $lines[] = '  body: ' . str_replace(["\n", "\r"], ' ', $h['body'] ?? '');
        }

        foreach ($this->debug['errors'] ?? [] as $i => $e) {
            $lines[] = 'error[' . $i . ']: ' . str_replace(["\n", "\r"], ' ', $e);
        }

        $lines[] = '=== POST_CERT_DEBUG_END ===';
        $block = implode("\n", $lines);

        // stdout → Artisan::output() → JSON UI
        $this->line($block);
        Log::error($block);

        try {
            $path = storage_path('logs/post-certificate-last.txt');
            file_put_contents($path, $block . "\n");
        } catch (\Throwable $e) {
            // ignore write fail
        }
    }

    /**
     * tgl dari jadwal course, fallback min/max finished_at peserta.
     */
    private function resolveDates($courseItem, $learnRecords): array
    {
        $schedule = $courseItem->course->schedules->first();
        if ($schedule && $schedule->date) {
            $d = Carbon::parse($schedule->date)->format('Y-m-d');
            return ['tgl_mulai' => $d, 'tgl_selesai' => $d];
        }

        $min = $learnRecords->min('finished_at');
        $max = $learnRecords->max('finished_at');

        return [
            'tgl_mulai' => $min ? Carbon::parse($min)->format('Y-m-d') : now()->format('Y-m-d'),
            'tgl_selesai' => $max ? Carbon::parse($max)->format('Y-m-d') : now()->format('Y-m-d'),
        ];
    }

    private function sanitizeTitle(string $title): string
    {
        return strtr($title, [
            "\u{201C}" => '"',
            "\u{201D}" => '"',
            "\u{2018}" => "'",
            "\u{2019}" => "'",
            "\u{2013}" => '-',
            "\u{2014}" => '-',
        ]);
    }

    /**
     * Kirim request ke SSO. 401 → force refresh + retry 1x.
     * Header auth + Authorization: kompat SSO lama & baru.
     */
    private function ssoPostSertifikat($courseItem, array $listNip, string &$token, array $dates)
    {
        $payload = [
            'fc_kduker' => 0,
            'fc_kdsatker' => 0,
            'fc_kdsubsatker' => 0,
            'lama_diklat' => $courseItem->course->jam_pelajaran ?? 0,
            'jenis_waktu' => 'J',
            'kode_kegiatan' => $courseItem->course->category->code,
            'nama_kegiatan' => $this->sanitizeTitle($courseItem->course->title ?? ''),
            'tempat_kegiatan' => 'Dinas Komunikasi & Informatika',
            'penyelenggara' => 'BKPSDM Gresik',
            'tahun' => Carbon::now()->year,
            'tgl_mulai' => $dates['tgl_mulai'],
            'tgl_selesai' => $dates['tgl_selesai'],
            'peserta' => implode(',', $listNip),
        ];

        if (app()->environment('local') && empty(config('services.sso.base_url'))) {
            // Local tanpa SSO config: simulasi sukses (dev UI)
            Log::info('--- SIMULASI PUSH LOKAL (SSO_BASE_URL kosong) ---', [
                'nama_kegiatan' => $payload['nama_kegiatan'],
                'jml_peserta' => count($listNip),
                'payload' => $payload,
            ]);

            return new class {
                public function successful(): bool
                {
                    return true;
                }
                public function status(): int
                {
                    return 200;
                }
                public function body(): string
                {
                    return '{"message":"simulasi lokal"}';
                }
            };
        }

        $url = rtrim((string) config('services.sso.base_url'), '/') . '/api/post_sertifikat';

        $response = $this->httpPost($url, $payload, $token);

        Log::info('[PostCertificate] SSO request detail.', [
            'course' => $payload['nama_kegiatan'],
            'jml_peserta' => count($listNip),
            'http_status' => $response->status(),
            'response' => mb_substr($response->body(), 0, 500),
            'tgl_mulai' => $payload['tgl_mulai'],
            'peserta_sample' => array_slice($listNip, 0, 3),
        ]);

        if ($response->status() === 401) {
            Log::warning('[PostCertificate] Token 401 — force refresh dan retry.');
            $token = $this->tokenService->forceRefresh();
            $response = $this->httpPost($url, $payload, $token);
            Log::info('[PostCertificate] SSO retry setelah force refresh.', [
                'http_status' => $response->status(),
                'response' => mb_substr($response->body(), 0, 500),
            ]);
        }

        return $response;
    }

    private function httpPost(string $url, array $payload, string $token)
    {
        return Http::withHeaders([
            'apikey' => config('services.sso.api_key'),
            'Authorization' => $token,
            'auth' => $token, // SSO lama
        ])
            ->acceptJson()
            ->asForm()
            ->timeout(60)
            ->post($url, $payload);
    }
}
