<?php

namespace App\Http\Controllers\Admin;

use App\Models\Learn\Learn;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Yajra\DataTables\Facades\DataTables;

class CertificateController
{
    public function index()
    {
        if (request()->ajax()) {
            $response = Learn::with(['course', 'participant'])
                ->whereNotNull('finished_at')
                ->get()
                ->map(function ($row) {
                    // Determine certificate status
                    if (!$row->finished_at) {
                        $status = '<span class="badge bg-secondary">Belum Selesai</span>';
                    } elseif (!$row->is_post_certificate) {
                        $status = '<span class="badge bg-warning text-dark">Menunggu Sync</span>';
                    } elseif ($row->is_post_certificate && !$row->url_certificate) {
                        $status = '<span class="badge bg-info text-dark">Dikirim ke SSO</span>';
                    } else {
                        $status = '<span class="badge bg-success">Sertifikat Tersedia</span>';
                    }

                    // Certificate action
                    if ($row->url_certificate && $row->url_certificate !== '-') {
                        $action = '<a href="' . $row->url_certificate . '" target="_blank" class="btn btn-sm btn-primary">
                                        <i class="ti ti-download me-1"></i>Download
                                    </a>';
                    } else {
                        $action = '<span class="text-muted">-</span>';
                    }

                    // NIP & unit from user_meta
                    $nip      = get_user_meta($row->participant_id, 'nip') ?: '-';
                    $satker   = get_user_meta($row->participant_id, 'nama_jabatan') ?: '-';

                    return [
                        'name'         => $row->participant->name ?? '-',
                        'nip'          => $nip,
                        'satker'       => $satker,
                        'course'       => $row->course->title ?? '-',
                        'finished_at'  => $row->finished_at ? Carbon::parse($row->finished_at)->format('d/m/Y') : '-',
                        'status'       => $status,
                        'url_certificate' => $action,
                    ];
                });

            return DataTables::of($response)
                ->addIndexColumn()
                ->rawColumns(['status', 'url_certificate'])
                ->make(true);
        }

        return view('admin.certificate.index');
    }

    /**
     * Trigger push certificate manually from admin UI.
     */
    public function syncPush()
    {
        // Cegah DebugBar menyisipkan HTML ke JSON response
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        try {
            $exitCode = Artisan::call('app:post-certificate-command');
            $output   = trim(Artisan::output());

            if ($exitCode !== 0) {
                return response()->json([
                    'message' => 'Sync Push selesai dengan error: ' . ($output ?: 'Periksa log server.'),
                ], 422);
            }

            return response()->json([
                'message' => 'Sync Push berhasil. ' . $output,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Sync Push gagal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Trigger pull (fetch URL) certificate manually from admin UI.
     */
    public function syncPull()
    {
        // Cegah DebugBar menyisipkan HTML ke JSON response
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        try {
            $exitCode = Artisan::call('app:set-url-certificate-command');
            $output   = trim(Artisan::output());

            if ($exitCode !== 0) {
                return response()->json([
                    'message' => 'Sync Pull selesai dengan error: ' . ($output ?: 'Periksa log server.'),
                ], 422);
            }

            return response()->json([
                'message' => 'Sync Pull berhasil. ' . $output,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Sync Pull gagal: ' . $e->getMessage()], 500);
        }
    }
}
