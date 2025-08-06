<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\Learn\Learn;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class PostCertificateCommand extends Command
{
    protected $signature = 'app:post-certificate-command';

    protected $description = 'Mengirim data sertifikat peserta dari pelatihan yang selesai hari ini.';

    public function handle(): void
    {
        try {
            $responseOauth = Http::retry(3, 100)->withHeaders([
                'apikey' => env('API_KEY')
            ])->post(env('SSO_BASE_URL') . '/oauth/token', [
                'username'      => env('SSO_ADMIN_USERNAME'),
                'password'      => env('SSO_ADMIN_PASS'),
                'grant_type'    => 'password',
                'client_id'     => env('SSO_CLIENT_ID'),
                'client_secret' => env('SSO_CLIENT_SECRET'),
            ]);

            $token = null;

            if ($responseOauth->successful() && isset($responseOauth->json()['access_token'])) {
                $token = 'Bearer ' . $responseOauth->json()['access_token'];
                Log::info('OAuth token obtained.');
            } else {
                Log::warning('Gagal mendapatkan token OAuth.', [
                    'response' => $responseOauth->body()
                ]);
                return;
            }

            $courses = Learn::with('course.category')
                ->whereDate('finished_at', Carbon::today())
                ->where('is_post_certificate', 0)
                ->get();

            if ($courses->isEmpty()) {
                Log::info('Tidak ada course yang selesai hari ini.');
                return;
            }

            foreach ($courses as $courseItem) {
                try {
                    DB::beginTransaction();

                    $participants = Learn::where('course_id', $courseItem->course->id)
                        ->whereDate('finished_at', Carbon::today())
                        ->where('is_post_certificate', 0)
                        ->get()
                        ->pluck('participant_id');

                    $listNip = [];
                    foreach ($participants as $participantId) {
                        $listNip[] = get_user_meta($participantId, 'nip');
                    }

                    $response = Http::withHeaders([
                        'apikey' => env('API_KEY'),
                        'auth'   => $token
                    ])->asForm()->post(env('SSO_BASE_URL') . '/api/post_sertifikat', [
                        'fc_kduker'        => 0,
                        'fc_kdsatker'      => 0,
                        'fc_kdsubsatker'   => 0,
                        'lama_diklat'      => 4,
                        'jenis_waktu'      => 'J',
                        'kode_kegiatan'    => $courseItem->course->category->code,
                        'nama_kegiatan'    => $courseItem->course->title,
                        'tempat_kegiatan'  => 'Dinas Komunikasi & Informatika',
                        'penyelenggara'    => 'BKPSDM Gresik',
                        'tahun'            => Carbon::now()->year,
                        'tgl_mulai'        => now()->format('Y-m-d'),
                        'tgl_selesai'      => now()->format('Y-m-d'),
                        'peserta'          => implode(',', $listNip),
                    ]);

                    if (!$response->successful()) {
                        DB::rollBack();

                        Log::warning('Gagal mengirim data sertifikat.', [
                            'course_id' => $courseItem->id,
                            'response'  => $response->body()
                        ]);
                        continue;
                    }

                    Learn::where('course_id', $courseItem->course->id)
                        ->whereDate('finished_at', Carbon::today())
                        ->where('is_post_certificate', 0)
                        ->update(['is_post_certificate' => 1]);

                    DB::commit();

                    Log::info('Sertifikat berhasil diposting.', [
                        'course_id' => $courseItem->id
                    ]);
                } catch (\Throwable $th) {
                    DB::rollBack();

                    Log::error('Exception saat posting sertifikat:', [
                        'message'   => $th->getMessage(),
                        'course_id' => $courseItem->id
                    ]);
                }
            }
        } catch (\Throwable $th) {
            Log::error('Exception utama:', [
                'message' => $th->getMessage()
            ]);
        }
    }
}
