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
use Illuminate\Support\Facades\Session;

class BulkCertificateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $item, $token;

    public function __construct($item, $token)
    {
        $this->item = $item;
        $this->token = $token;
    }

    public function handle(): void
    {
        $response = Http::withHeaders([
            'Authorization' => $this->token,
        ])->acceptJson()->asForm()
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
