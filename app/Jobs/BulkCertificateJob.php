<?php

namespace App\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Services\Auth\TokenService;

class BulkCertificateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $item;

    public function __construct($item)
    {
        $this->item = $item;
    }

    public function handle(TokenService $tokens): void
    {
        $token = $tokens->getToken();
        $request = fn () => Http::withToken(substr($token, 7))->acceptJson()->asForm()
            ->post(rtrim((string) config('services.sso.base_url'), '/').'/api/post_sertifikat', [
                'fc_kduker' => $this->item->fc_kduker_meta ?? 0,
                'fc_kdsatker' => $this->item->fc_kdsatker_meta ?? 0,
                'fc_kdsubsatker' => $this->item->fc_kdsubsatker_meta ?? 0,
                'lama_diklat' => 4,
                'jenis_waktu' => 'J',
                'kode_kegiatan' => 3,
                'nama_kegiatan' => $this->item->title,
                'tempat_kegiatan' => 'Dinas Komunikasi & Informatika',
                'penyelenggara' => 'BKPSDM Gresik',
                'tahun' => Carbon::now()->year,
                'tgl_mulai' => $this->item->created_at,
                'tgl_selesai' => $this->item->finished_at,
                'peserta' => $this->item->nip
            ]);
        $response = $request();
        if ($response->status() === 401) {
            $token = $tokens->forceRefresh();
            $request = fn () => Http::withToken(substr($token, 7))->acceptJson()->asForm()
                ->post(rtrim((string) config('services.sso.base_url'), '/').'/api/post_sertifikat', [
                    'fc_kduker' => $this->item->fc_kduker_meta ?? 0, 'fc_kdsatker' => $this->item->fc_kdsatker_meta ?? 0,
                    'fc_kdsubsatker' => $this->item->fc_kdsubsatker_meta ?? 0, 'lama_diklat' => 4, 'jenis_waktu' => 'J',
                    'kode_kegiatan' => 3, 'nama_kegiatan' => $this->item->title, 'tempat_kegiatan' => 'Dinas Komunikasi & Informatika',
                    'penyelenggara' => 'BKPSDM Gresik', 'tahun' => Carbon::now()->year, 'tgl_mulai' => $this->item->created_at,
                    'tgl_selesai' => $this->item->finished_at, 'peserta' => $this->item->nip,
                ]);
            $response = $request();
        }

        if ($response->successful()) {
            // Logika jika berhasil
            Log::info('Request berhasil dikirim.', ['response' => $response->json()]);
        } else {
            // Logika jika gagal
            Log::error('Request gagal dikirim.', [
                'status' => $response->status(),
                'error' => $response->body()
            ]);
        }
    }
}
