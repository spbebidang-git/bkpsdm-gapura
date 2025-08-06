<?php

namespace App\Http\Controllers\Participant;

use App\Models\Course\Course;
use App\Models\Learn\Learn;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class CertificateController
{
    public function __invoke(Request $request)
    {
        $token = Session::get('token');

        # request sertifikat 
        // $participantApi = Http::withHeaders([
        //     'apikey' => env('API_KEY'),
        //     'auth' => $token
        // ])->post(env('SSO_BASE_URL') . '/api/get_nip_data');
        try {
            $response = Http::withHeaders([
                'apikey' => env('API_KEY'),
                'auth' => $token
            ])->asForm()
                ->post(env('SSO_BASE_URL') . '/api/get_data_sertifikat', [
                    'nip' => get_user_meta(Auth::user()->id, 'nip'),
                    'kode' => 135,
                    'kegiatan' => $request->course,
                ])
                ->throw();

            $array = json_decode($response->body(), true);

            // Filter data: cari yang `nomor_sertifikat` tidak null atau `sertifikat` mengandung http
            $filtered = collect($array)->filter(function ($item) {
                return !is_null($item['nomor_sertifikat']) || str_contains($item['sertifikat'], 'http');
            })->values();

            // Ambil satu data
            $selected = $filtered->first();

            if ($selected && str_contains($selected['sertifikat'], 'http')) {
                return redirect()->back()->with('redirect_url', $selected['sertifikat']);
            } else {
                // Jika tidak ditemukan sertifikat dengan URL valid
                return redirect()->back()->with('danger', 'Sertifikat tidak ditemukan atau belum tersedia.');
            }
        } catch (\Throwable $th) {
            return redirect()->back()->with('danger', 'Something went wrong. Please try again.');
        }



        # find array by course 

    }
}
