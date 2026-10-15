<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Learning\Learn;
use App\Services\Auth\TokenService;
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

    /** Profile request yang ketemu jalan (null = belum / semua gagal). */
    protected ?string $workingProfile = null;

    /** Sudah pernah auto-probe di run ini? */
    protected bool $probed = false;

    public function handle(TokenService $tokenService): int
    {
        $this->tokenService = $tokenService;
        $this->debug = [
            'time' => now()->toDateTimeString(),
            'env' => app()->environment(),
            'sso_base' => config('services.sso.base_url') ?: '(KOSONG)',
            'client_id_set' => config('services.sso.client_id') ? 'yes' : 'NO',
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
            $skippedParticipants = 0;

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

                        foreach ($chunk as $i => $entry) {
                            // Probe global gagal → stop, jangan 1000x request sia-sia
                            if ($this->probed && $this->workingProfile === null && $pushedParticipants === 0) {
                                $left = count($chunk) - $i;
                                $failedParticipants += $left;
                                $msg = 'SSO tolak semua format request — stop. NIP sisa dihitung gagal (bukan skip).';
                                $this->error($msg);
                                $this->rememberError($msg);
                                break 2;
                            }

                            try {
                                $solo = $this->ssoPostSertifikat($courseItem, [$entry['nip']], $token, $dates);
                                if ($solo->successful()) {
                                    Learn::where('id', $entry['learn_id'])->update(['is_post_certificate' => 1]);
                                    $pushedParticipants++;
                                    continue;
                                }

                                // NIP ditolak Prestige (sering: tidak terdaftar) → skip, lanjut NIP lain
                                $skippedParticipants++;
                                $soloBody = mb_substr($solo->body(), 0, 200);
                                Log::warning('[PostCertificate] NIP skip (SSO tolak / tidak terdaftar).', [
                                    'nip' => $entry['nip'],
                                    'learn_id' => $entry['learn_id'],
                                    'http' => $solo->status(),
                                    'body' => $soloBody,
                                ]);
                                $this->warn("Skip NIP {$entry['nip']} HTTP {$solo->status()}");
                                $this->rememberError("Skip NIP {$entry['nip']} HTTP {$solo->status()}: {$soloBody}");
                                $this->recordHttpSample('skip', $courseIdKey, $batchNo, $solo->status(), $soloBody, [$entry['nip']], $dates);
                            } catch (\Throwable $th) {
                                $skippedParticipants++;
                                $this->rememberError("Skip NIP {$entry['nip']}: " . $th->getMessage());
                                Log::warning('[PostCertificate] NIP skip (exception).', [
                                    'nip' => $entry['nip'],
                                    'message' => $th->getMessage(),
                                ]);
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

            $hint = $this->errorSamples ? ' | ' . $this->errorSamples[0] : '';
            $this->info("Push selesai. Berhasil: {$pushedParticipants}, Skip: {$skippedParticipants}, Gagal: {$failedParticipants}{$hint}");

            $this->debug['result'] = [
                'berhasil' => $pushedParticipants,
                'skip' => $skippedParticipants,
                'gagal' => $failedParticipants,
            ];
            $this->debug['errors'] = $this->errorSamples;
            $this->emitDebugReport();

            // Skip NIP (tidak terdaftar) bukan failure. Gagal total hanya kalau 0 sukses + ada gagal global.
            return $pushedParticipants === 0 && $failedParticipants > 0
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
        $lines[] = 'client_id_set: ' . ($this->debug['client_id_set'] ?? '');
        $lines[] = 'token: ' . ($this->debug['token'] ?? '');
        $lines[] = 'filter_course: ' . ($this->debug['filters']['course'] ?? '-');
        $lines[] = 'filter_learn: ' . ($this->debug['filters']['learn'] ?? '-');

        if (!empty($this->debug['fatal'])) {
            $lines[] = 'fatal: ' . $this->debug['fatal'];
        }

        if (!empty($this->debug['result'])) {
            $lines[] = 'berhasil: ' . $this->debug['result']['berhasil'];
            $lines[] = 'skip: ' . ($this->debug['result']['skip'] ?? 0);
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

        if (!empty($this->debug['working_profile'])) {
            $lines[] = 'working_profile: ' . $this->debug['working_profile'];
        }

        foreach ($this->debug['probe'] ?? [] as $i => $p) {
            $lines[] = sprintf(
                'probe[%d] profile=%s http=%s kode=%s body=%s',
                $i,
                $p['profile'] ?? '',
                $p['http'] ?? '',
                $p['kode'] ?? '',
                $p['body'] ?? ''
            );
        }

        foreach ($this->debug['errors'] ?? [] as $i => $e) {
            $lines[] = 'error[' . $i . ']: ' . str_replace(["\n", "\r"], ' ', $e);
        }

        $lines[] = '=== POST_CERT_DEBUG_END ===';
        $block = implode("\n", array_map(fn ($line) => app(\App\Logging\Redactor::class)->text($line), $lines));

        // stdout → Artisan::output() → JSON UI
        $this->line($block);
        Log::error($block);

        try {
            $path = storage_path('logs/post-certificate-last.txt');
            file_put_contents($path, $block . "\n", LOCK_EX);
            chmod($path, 0600);
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
     * Kirim ke SSO. Kalau gagal & belum punya working profile → auto-probe format.
     */
    private function ssoPostSertifikat($courseItem, array $listNip, string &$token, array $dates)
    {
        if (app()->environment('local') && empty(config('services.sso.base_url'))) {
            Log::info('--- SIMULASI PUSH LOKAL (SSO_BASE_URL kosong) ---', [
                'jml_peserta' => count($listNip),
            ]);

            return new class {
                public function successful(): bool { return true; }
                public function status(): int { return 200; }
                public function body(): string { return '{"message":"simulasi lokal"}'; }
            };
        }

        $url = rtrim((string) config('services.sso.base_url'), '/') . '/api/post_sertifikat';
        $profile = $this->workingProfile ?? 'authorization_only';
        $payload = $this->buildPayload($courseItem, $listNip, $dates, $profile);

        $response = $this->httpPost($url, $payload, $token, $profile);

        Log::info('[PostCertificate] SSO request detail.', [
            'profile' => $profile,
            'course' => $payload['nama_kegiatan'] ?? '',
            'kode_kegiatan' => $payload['kode_kegiatan'] ?? null,
            'jml_peserta' => count($listNip),
            'http_status' => $response->status(),
            'response' => mb_substr($response->body(), 0, 500),
            'peserta_sample' => array_slice($listNip, 0, 3),
        ]);

        if ($response->status() === 401) {
            Log::warning('[PostCertificate] Token 401 — force refresh dan retry.');
            $token = $this->tokenService->forceRefresh();
            $response = $this->httpPost($url, $payload, $token, $profile);
        }

        // Auto-probe 1x per run: cari format yang SSO terima
        if (!$response->successful() && !$this->probed && count($listNip) >= 1) {
            $this->probed = true;
            $won = $this->probeProfiles($courseItem, $listNip[0], $token, $dates, $url);
            if ($won !== null) {
                $this->workingProfile = $won;
                $this->debug['working_profile'] = $won;
                $payload = $this->buildPayload($courseItem, $listNip, $dates, $won);
                $response = $this->httpPost($url, $payload, $token, $won);
                Log::info('[PostCertificate] Retry pakai profile menang.', [
                    'profile' => $won,
                    'http_status' => $response->status(),
                    'body' => mb_substr($response->body(), 0, 300),
                ]);
            } else {
                $this->debug['working_profile'] = 'NONE';
            }
        }

        return $response;
    }

    /**
     * Coba beberapa format request (header/kode/title) — 1 NIP saja.
     * @return string|null nama profile yang HTTP 2xx
     */
    private function probeProfiles($courseItem, string $nip, string &$token, array $dates, string $url): ?string
    {
        $profiles = [
            'authorization_only',
            'legacy_auth',           // auth only, code, no Accept JSON (asli RatingController-ish)
            'legacy_auth_cat_id',    // auth only, category_id (RatingController paling lama)
            'auth_bearer_both',      // auth + Authorization + code
            'auth_short_title',      // auth only, title pendek
            'auth_kode3',            // auth only, kode=3 (BulkCertificateJob)
            'authorization_only',    // Authorization only + acceptJson (versi baru)
        ];

        $results = [];
        foreach ($profiles as $profile) {
            $payload = $this->buildPayload($courseItem, [$nip], $dates, $profile);
            try {
                $res = $this->httpPost($url, $payload, $token, $profile);
                if ($res->status() === 401) {
                    $token = $this->tokenService->forceRefresh();
                    $res = $this->httpPost($url, $payload, $token, $profile);
                }
                $row = [
                    'profile' => $profile,
                    'http' => $res->status(),
                    'body' => mb_substr(str_replace(["\n", "\r"], ' ', $res->body()), 0, 180),
                    'kode' => $payload['kode_kegiatan'] ?? null,
                    'title' => mb_substr($payload['nama_kegiatan'] ?? '', 0, 60),
                ];
                $results[] = $row;
                Log::warning('[PostCertificate] PROBE', $row);

                if ($res->successful()) {
                    $this->debug['probe'] = $results;
                    $this->line('[PROBE] MENANG: ' . $profile . ' HTTP ' . $res->status());
                    return $profile;
                }
            } catch (\Throwable $e) {
                $results[] = [
                    'profile' => $profile,
                    'http' => 'EX',
                    'body' => $e->getMessage(),
                ];
            }
            usleep(200000);
        }

        $this->debug['probe'] = $results;
        $this->line('[PROBE] semua gagal. Lihat debug probe.');
        return null;
    }

    private function buildPayload($courseItem, array $listNip, array $dates, string $profile): array
    {
        $title = $this->sanitizeTitle($courseItem->course->title ?? '');
        $code = $courseItem->course->category->code ?? 0;
        $catId = $courseItem->course->category_id ?? $courseItem->course->category->id ?? 0;

        $kode = $code;
        if (str_contains($profile, 'cat_id')) {
            $kode = $catId;
        } elseif (str_contains($profile, 'kode3')) {
            $kode = 3;
        }

        if (str_contains($profile, 'short_title')) {
            $title = 'Gapura Pelatihan ' . ($courseItem->course->id ?? '');
        }

        return [
            'fc_kduker' => 0,
            'fc_kdsatker' => 0,
            'fc_kdsubsatker' => 0,
            'lama_diklat' => $courseItem->course->jam_pelajaran ?? 0,
            'jenis_waktu' => 'J',
            'kode_kegiatan' => $kode,
            'nama_kegiatan' => $title,
            'tempat_kegiatan' => 'Dinas Komunikasi & Informatika',
            'penyelenggara' => 'BKPSDM Gresik',
            'tahun' => Carbon::now()->year,
            'tgl_mulai' => $dates['tgl_mulai'],
            'tgl_selesai' => $dates['tgl_selesai'],
            'peserta' => implode(',', $listNip),
        ];
    }

    private function httpPost(string $url, array $payload, string $token, string $profile = 'legacy_auth')
    {
        $headers = [];

        // Prestige SSO historis: header `auth` (bukan Authorization)
        if ($profile === 'authorization_only') {
            $headers['Authorization'] = $token;
        } elseif ($profile === 'auth_bearer_both') {
            $headers['Authorization'] = $token;
            $headers['auth'] = $token;
        } else {
            $headers['auth'] = $token;
        }

        $req = Http::withHeaders($headers)->asForm()->timeout(60);

        if ($profile === 'authorization_only' || $profile === 'auth_bearer_both') {
            $req = $req->acceptJson();
        }

        return $req->post($url, $payload);
    }
}
