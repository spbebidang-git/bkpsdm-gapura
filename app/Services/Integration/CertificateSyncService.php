<?php

namespace App\Services\Integration;

use App\Repositories\Learning\Contracts\LearnRepositoryInterface;
use App\Services\Auth\TokenService;
use App\Services\Learning\LearnLifecycleService;
use App\Services\Learning\ParticipantNotificationService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CertificateSyncService
{
    public function __construct(
        private readonly TokenService $tokenService,
        private readonly LearnRepositoryInterface $learns,
    ) {}

    public function syncPull(?string $courseId = null, ?string $learnId = null, bool $onlyHasUrl = false, bool $onlyMissing = false): array
    {
        $validator = app(ParticipantNotificationService::class);
        $targets = $this->learns->getForCertificateSync($courseId, $learnId, $onlyHasUrl)
            ->loadMissing('participant.metas')
            ->filter(fn ($learn) => $learn->finished_at && $learn->is_post_certificate && $learn->prestige_certificate_registered_at
                && (! $onlyMissing || ! $validator->validCertificateUrl($learn->url_certificate)));
        $groups = $targets->filter(function ($learn) {
            if (! filled($learn->course?->prestige_certificate_code)) {
                Log::warning('Kode sertifikat belum tersedia; pengambilan dilewati.', ['event' => 'certificate.pull.code_missing','course_id' => $learn->course_id, 'learn_id' => $learn->id]);

                return false;
            }

            return true;
        })->groupBy(fn ($learn) => $learn->course->prestige_certificate_code);
        if ($groups->isEmpty()) {
            return ['updated' => 0, 'message' => 'Tidak ada sertifikat yang siap diambil. Pastikan kegiatan dan pegawai sudah dikirim serta kode Prestige tersimpan.'];
        }

        $token = $this->tokenService->getToken();
        $url = rtrim((string) config('services.sso.base_url'), '/').'/api/get_data_sertifikat';
        $updated = 0;
        foreach ($groups as $code => $learns) {
            $attempt = 0;
            $send = function () use (&$token, &$attempt, $url, $code, $courseId, $learnId) {
                $parameters = ['nip' => '-', 'kode' => (string) $code, 'kegiatan' => '-', 'kode_kegiatan' => '-'];
                $exchange = CertificateHttpLogger::request($url, 'multipart/form-data', $parameters, ++$attempt, [
                    'kode' => $code, 'course_id' => $courseId, 'learn_id' => $learnId,
                ]);
                try {
                    $response = Http::withHeaders(['Authorization' => $token])->acceptJson()->asMultipart()->timeout(60)->post($url, $parameters);
                } catch (\Throwable $e) {
                    CertificateHttpLogger::failed($exchange, $e);
                    throw $e;
                }
                CertificateHttpLogger::response($exchange, $response, '[CertificateSync] Detail respons ambil sertifikat Prestige.');

                return $response;
            };
            $response = $send();
            if ($response->status() === 401) {
                $token = $this->tokenService->forceRefresh();
                $response = $send();
            }
            $rows = CertificateResponse::data($response);
            if (! array_is_list($rows)) {
                throw new RuntimeException('Format daftar sertifikat Prestige tidak valid.');
            }
            $updates = [];
            $conflicts = [];
            // Match within the requested activity code. A title or date change must not change its identity.
            $byNip = $learns->groupBy(fn ($learn) => normalize_nip($learn->participant?->nip) ?? '');
            foreach ($rows as $row) {
                if (! is_array($row) || ! is_string($row['nama_kegiatan'] ?? null) || ! is_array($row['peserta'] ?? null)) {
                    throw new RuntimeException('Format kegiatan sertifikat Prestige tidak valid.');
                }
                if (isset($row['kode']) && trim((string) $row['kode']) !== (string) $code) {
                    Log::warning('Kode kegiatan respons berbeda; pengambilan dilewati.', ['event' => 'certificate.pull.code_mismatch','kode' => $code]);

                    continue;
                }
                foreach ($row['peserta'] as $participant) {
                    if (! is_array($participant)) {
                        throw new RuntimeException('Format pengguna sertifikat Prestige tidak valid.');
                    }
                    $nip = normalize_nip($participant['nip'] ?? null);
                    $certificate = $participant['sertifikat'] ?? null;
                    if (! $nip || ! is_string($certificate) || ! $validator->validCertificateUrl(trim($certificate))) {
                        continue;
                    }
                    $matches = $byNip->get($nip, collect());
                    if ($matches->count() !== 1) {
                        if ($matches->count() > 1) {
                            Log::warning('Pengguna kegiatan ambigu; pembaruan dilewati.', ['event' => 'certificate.pull.ambiguous','kode' => $code]);
                        }

                        continue;
                    }
                    $learn = $matches->first();
                    $certificate = trim($certificate);
                    if (isset($updates[$learn->id]) && $updates[$learn->id] !== $certificate) {
                        $conflicts[$learn->id] = true;
                        Log::warning('URL sertifikat konflik; pembaruan dilewati.', ['event' => 'certificate.pull.conflict','kode' => $code, 'learn_id' => $learn->id]);
                    }
                    $updates[$learn->id] = $certificate;
                }
            }
            foreach ($updates as $id => $certificate) {
                if (! isset($conflicts[$id]) && app(LearnLifecycleService::class)->setCertificate($learns->firstWhere('id', $id), $certificate)) {
                    $updated++;
                }
            }
        }

        Log::info('Pengambilan sertifikat selesai.', ['event' => 'certificate.pull.completed', 'updated_count' => $updated]);

        return ['updated' => $updated, 'message' => "Pull selesai. {$updated} sertifikat berhasil disinkronisasi."];
    }
}
