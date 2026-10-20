<?php

namespace App\Console\Commands;

use App\Models\Learning\Learn;
use App\Services\Auth\TokenService;
use App\Services\Integration\CertificateHttpLogger;
use App\Services\Integration\CertificateResponse;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PostCertificateCommand extends Command
{
    protected $signature = 'app:post-certificate-command {--course= : Filter by specific course ID} {--learn= : Filter by specific learn ID}';

    protected $description = 'Mengirim data sertifikat peserta dari pelatihan yang selesai ke SSO (chunk per batch).';

    /** Max NIP per request ke SSO — hindari 500 body/timeout. */
    protected int $chunkSize = 50;

    protected TokenService $tokenService;

    /** @var string[] cuplikan error buat output UI */
    protected array $errorSamples = [];

    public function handle(TokenService $tokenService): int
    {
        $this->errorSamples = [];
        $this->tokenService = $tokenService;
        $lock = Cache::lock('prestige:certificate:push', 3600);
        if (! $lock->get()) {
            Log::warning('Pengiriman sertifikat lain masih berjalan.', ['event' => 'certificate.push.locked']);
            $this->error('Pengiriman sertifikat lain masih berjalan.');

            return Command::FAILURE;
        }
        try {
            $this->info('[PostCertificate] Mulai proses push sertifikat.');
            Log::info('Pengiriman sertifikat dimulai.', ['event' => 'certificate.push.started']);

            $token = $tokenService->getToken();

            $this->info('[PostCertificate] Token berhasil diambil.');


            $query = Learn::with(['course.category', 'course.schedules', 'participant.metas'])
                ->whereNull('prestige_certificate_registered_at');

            $courseId = $this->option('course');
            $learnId = $this->option('learn');

            if ($learnId) {
                // Filter satu peserta tetap menghormati status kirim dan kelulusan.
                $query->where('id', $learnId)->whereNotNull('finished_at');
                $this->info('[PostCertificate] Filter aktif: learn_id = '.$learnId);
            } elseif ($courseId) {
                $query->where('course_id', $courseId)
                    ->whereNotNull('finished_at');
                $this->info('[PostCertificate] Filter aktif: course_id = '.$courseId);
            } else {
                $query->whereNotNull('finished_at');
            }

            $allLearns = $query->get();

            $this->info('[PostCertificate] Data learns ditemukan: '.$allLearns->count().' record yang belum dikirim.');
            Log::info('Peserta siap dikirim.', ['event' => 'certificate.push.targets', 'count' => $allLearns->count()]);

            if ($allLearns->isEmpty()) {
                Log::info('Tidak ada sertifikat yang perlu dikirim.', ['event' => 'certificate.push.completed', 'success_count' => 0, 'failed_count' => 0, 'skipped_count' => 0]);
                $this->info('Tidak ada data yang perlu di-push.');

                return Command::SUCCESS;
            }

            $groupedByCourse = $allLearns->groupBy('course_id');

            $this->info('[PostCertificate] Jumlah course unik: '.$groupedByCourse->count().' | chunk size: '.$this->chunkSize);
            Log::info('Kelas dikelompokkan untuk pengiriman.', [
                'event' => 'certificate.push.grouped', 'count' => $groupedByCourse->count(),
                'chunk_size' => $this->chunkSize,
            ]);

            $pushedParticipants = 0;
            $failedParticipants = 0;
            $skippedParticipants = 0;

            foreach ($groupedByCourse as $courseIdKey => $learnRecords) {
                $courseItem = $learnRecords->first();

                if (! $courseItem->course) {
                    $failedParticipants += $learnRecords->count();
                    $msg = "Course ID {$courseIdKey} tidak ditemukan di tabel courses, skip.";
                    $this->error($msg);
                    $this->rememberError($msg);
                    Log::error('Kelas tidak ditemukan.', ['event' => 'certificate.course.missing', 'course_id' => $courseIdKey]);

                    continue;
                }

                if (! $courseItem->course->category || ! filled($courseItem->course->category->code)) {
                    $failedParticipants += $learnRecords->count();
                    $msg = 'Kelas ID '.$courseIdKey.' tidak memiliki kategori/kode kegiatan, dilewati.';
                    $this->error($msg);
                    $this->rememberError($msg);
                    Log::error('Kategori kelas belum tersedia.', ['event' => 'certificate.category.missing', 'course_id' => $courseIdKey]);

                    continue;
                }

                $entries = [];
                $seenParticipants = [];

                foreach ($learnRecords as $learn) {
                    if (isset($seenParticipants[$learn->participant_id])) {
                        continue;
                    }

                    $rawNip = $learn->participant?->nip;
                    $nip = normalize_nip($rawNip);
                    if (! $nip) {
                        $failedParticipants++;
                        $this->warn("Peserta ID {$learn->participant_id} NIP invalid/kosong, dilewati.");
                        Log::warning('Identitas peserta belum valid.', [
                            'event' => 'certificate.participant.invalid', 'participant_id' => $learn->participant_id,
                            'participant_exists' => $learn->participant !== null,
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
                    $msg = 'Kelas ID '.$courseIdKey.' tidak memiliki peserta dengan identitas valid.';
                    $this->error($msg);
                    $this->rememberError($msg);
                    Log::warning('Kelas tidak memiliki peserta valid.', ['event' => 'certificate.push.no_valid_participants', 'course_id' => $courseIdKey]);

                    continue;
                }

                $dates = $this->resolveDates($courseItem, $learnRecords);
                $chunks = array_chunk($entries, $this->chunkSize);
                $totalChunks = count($chunks);

                $this->info('Kelas ID '.$courseIdKey.': '.count($entries).' peserta → '.$totalChunks.' batch.');

                $code = $courseItem->course->prestige_certificate_code;
                $missing = [];
                if (! filled($code) && $courseItem->course->learns()->where('is_post_certificate', 1)->exists()) {
                    $failedParticipants += count($entries);
                    $msg = 'Data kirim lama belum menyimpan kode Prestige. Isi prestige_certificate_code dari hasil kirim sebelumnya sebelum melanjutkan.';
                    $this->rememberError($msg);
                    Log::warning('Kode pengiriman lama perlu dilengkapi.', ['event' => 'certificate.activity.code_missing', 'course_id' => $courseIdKey]);

                    continue;
                }
                if (! filled($code)) {
                    try {
                        // Create the activity once. Subsequent batches and future runs reuse its code.
                        $response = $this->ssoPostSertifikat($courseItem, array_column($chunks[0], 'nip'), $token, $dates);
                        if (! CertificateResponse::accepted($response)) {
                            throw new \RuntimeException('post_sertifikat gagal atau respons tidak valid (HTTP '.$response->status().').');
                        }
                        $data = CertificateResponse::data($response);
                        $code = (string) $data['kode'];
                        // Persist before attaching employees, so a failed attachment does not recreate the activity.
                        $courseItem->course->update(['prestige_certificate_code' => $code]);
                        $missing = array_map('normalize_nip', $data['nip_tidak_ditemukan']);
                        Log::info('Kegiatan sertifikat tersimpan.', ['event' => 'certificate.activity.created', 'course_id' => $courseIdKey, 'kode' => $code]);
                    } catch (\Throwable $e) {
                        $failedParticipants += count($entries);
                        $this->rememberError($e->getMessage());
                        Log::error('Pembuatan kegiatan sertifikat gagal.', ['event' => 'certificate.activity.failed', 'course_id' => $courseIdKey, 'exception' => $e]);

                        continue;
                    }
                }

                foreach ($entries as $entry) {
                    if (in_array($entry['nip'], $missing, true)) {
                        $failedParticipants++;
                        $this->rememberError('NIP tidak ditemukan di Prestige.');

                        continue;
                    }
                    try {
                        $response = $this->postPrestige('/api/post_sertifikat_pegawai', ['kode' => $code, 'nip' => $entry['nip']], $token, ['course_id' => $courseIdKey, 'learn_id' => $entry['learn_id']]);
                        $data = CertificateResponse::data($response);
                        if (($data['message'] ?? null) !== 'Berhasil Disimpan') {
                            throw new \RuntimeException('Respons post_sertifikat_pegawai tidak menyatakan berhasil.');
                        }
                        Learn::whereKey($entry['learn_id'])->update(['is_post_certificate' => 1, 'prestige_certificate_registered_at' => now()]);
                        $pushedParticipants++;
                        Log::info('Sertifikat peserta terdaftar.', ['event' => 'certificate.employee.registered', 'kode' => $code, 'learn_id' => $entry['learn_id']]);
                    } catch (\Throwable $e) {
                        $failedParticipants++;
                        $this->rememberError($e->getMessage());
                        Log::warning('Pendaftaran sertifikat peserta gagal.', ['event' => 'certificate.employee.failed', 'kode' => $code, 'learn_id' => $entry['learn_id'], 'exception' => $e]);
                    }
                }

            }

            $hint = $this->errorSamples ? ' | Periksa log untuk rincian kegagalan.' : '';
            $this->info("Push selesai. Berhasil: {$pushedParticipants}, Skip: {$skippedParticipants}, Gagal: {$failedParticipants}{$hint}");

            $level = $failedParticipants === 0 ? 'info' : ($pushedParticipants > 0 ? 'warning' : 'error');
            Log::$level('Pengiriman sertifikat selesai.', [
                'event' => 'certificate.push.completed',
                'success_count' => $pushedParticipants,
                'skipped_count' => $skippedParticipants,
                'failed_count' => $failedParticipants,
            ]);

            // Surface partial failures to the scheduler and admin.
            return $failedParticipants > 0
                ? Command::FAILURE
                : Command::SUCCESS;
        } catch (\Throwable $th) {
            Log::error('Pengiriman sertifikat gagal.', ['event' => 'certificate.push.failed', 'exception' => $th]);
            $this->error('Pengiriman sertifikat gagal. Periksa log dengan ID proses terkait.');

            return Command::FAILURE;
        } finally {
            $lock->release();
        }
    }

    private function rememberError(string $msg): void
    {
        if (count($this->errorSamples) >= 3) {
            return;
        }
        $this->errorSamples[] = $msg;
    }

    /**
     * tgl dari jadwal course, fallback min/max finished_at peserta.
     */
    private function resolveDates($courseItem, $learnRecords): array
    {
        $scheduleDates = $courseItem->course->schedules->map(fn ($schedule) => $schedule->getRawOriginal('date'))->filter()->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'));
        if ($scheduleDates->isNotEmpty()) {
            return ['tgl_mulai' => $scheduleDates->min(), 'tgl_selesai' => $scheduleDates->max()];
        }

        $completed = $courseItem->course->learns()->whereNotNull('finished_at');
        $min = (clone $completed)->min('finished_at');
        $max = (clone $completed)->max('finished_at');

        return [
            'tgl_mulai' => $min ? Carbon::parse($min)->format('Y-m-d') : now()->format('Y-m-d'),
            'tgl_selesai' => $max ? Carbon::parse($max)->format('Y-m-d') : now()->format('Y-m-d'),
        ];
    }

    private function ssoPostSertifikat($courseItem, array $listNip, string &$token, array $dates)
    {
        return $this->postPrestige('/api/post_sertifikat', $this->buildPayload($courseItem, $listNip, $dates), $token, ['course_id' => $courseItem->course->id]);
    }

    private function postPrestige(string $path, array $payload, string &$token, array $context = [])
    {
        $url = rtrim((string) config('services.sso.base_url'), '/').$path;
        $attempt = 0;
        $send = function () use (&$token, &$attempt, $url, $payload, $context) {
            $exchange = CertificateHttpLogger::request($url, 'application/x-www-form-urlencoded', $payload, ++$attempt, $context);
            try {
                $response = Http::withHeaders(['Authorization' => $token])->acceptJson()->asForm()->timeout(60)->post($url, $payload);
            } catch (\Throwable $e) {
                CertificateHttpLogger::failed($exchange, $e);
                throw $e;
            }
            CertificateHttpLogger::response($exchange, $response, '[PostCertificate] Detail respons kirim sertifikat Prestige.');

            return $response;
        };
        $response = $send();
        if ($response->status() === 401) {
            $token = $this->tokenService->forceRefresh();
            $response = $send();
        }

        return $response;
    }

    private function buildPayload($courseItem, array $listNip, array $dates): array
    {
        $title = $courseItem->course->title ?? '';
        $code = $courseItem->course->category->code ?? 0;

        return [
            'fc_kduker' => 0,
            'fc_kdsatker' => 0,
            'fc_kdsubsatker' => 0,
            'lama_diklat' => $courseItem->course->jam_pelajaran ?? 0,
            'jenis_waktu' => 'J',
            'kode_kegiatan' => $code,
            'nama_kegiatan' => $title,
            'tempat_kegiatan' => 'Dinas Komunikasi & Informatika',
            'penyelenggara' => 'BKPSDM Gresik',
            'tahun' => Carbon::parse($dates['tgl_mulai'])->year,
            'tgl_mulai' => $dates['tgl_mulai'],
            'tgl_selesai' => $dates['tgl_selesai'],
            'peserta' => implode(',', $listNip),
        ];
    }
}
