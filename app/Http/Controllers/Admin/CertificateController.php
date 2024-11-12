<?php

namespace App\Http\Controllers\Admin;

use App\Http\Resources\Admin\CertificateResource;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class CertificateController
{
    public function index()
    {
        if (request()->ajax()) {
            $token = Session::get('token');

            $response = Http::withHeaders([
                'apikey' => env('API_KEY'),
                'auth' => $token
            ])->post(env('SSO_BASE_URL') . '/api/get_data_sertifikat')->json();

            $data = [];
            foreach ($response as $item) {
                foreach ($item['peserta'] as $item2) {
                    $data[] = [
                        'nip' => $item2['nip'],
                        'sertifikat' => $item2['sertifikat'],
                        'nama_kegiatan' => $item['nama_kegiatan'],
                        'penyelenggara' => $item['penyelenggara'],
                        'tempat_kegiatan' => $item['tempat_kegiatan'],
                        'tanggal' => $item['tgl_mulai'] . ' - ' . $item['tgl_selesai'],
                        'status' => $item['status']
                    ];
                }
            }

            return DataTables::of($data)
                ->addIndexColumn()
                ->make();
        }

        return view('admin.certificate.index');
    }
}
