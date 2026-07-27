<?php

namespace App\Http\Controllers\Admin;

use App\Models\Course\Course;
use App\Models\Learn\Learn;
use App\Services\CertificateSyncService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Yajra\DataTables\Facades\DataTables;

class CertificateController
{
    public function index()
    {
        if (request()->ajax()) {
            // Prevent DebugBar from injecting HTML into JSON response
            if (app()->bound('debugbar')) {
                app('debugbar')->disable();
            }

            $query = Learn::query()
                ->with(['course', 'participant'])
                ->whereNotNull('finished_at')
                ->latest('finished_at')
                ->latest('id')
                ->select('learns.*');

            // Apply status filter
            $statusFilter = request()->input('status_filter');
            if ($statusFilter === 'menunggu') {
                $query->where(function ($q) {
                    $q->where('is_post_certificate', false)
                        ->orWhereNull('is_post_certificate');
                })->where(function ($q) {
                    $q->whereNull('url_certificate')
                        ->orWhere('url_certificate', '')
                        ->orWhere('url_certificate', '-');
                });
            } elseif ($statusFilter === 'dikirim') {
                $query->where('is_post_certificate', true)
                    ->where(function ($q) {
                        $q->whereNull('url_certificate')
                            ->orWhere('url_certificate', '')
                            ->orWhere('url_certificate', '-');
                    });
            } elseif ($statusFilter === 'tersedia') {
                $query->whereNotNull('url_certificate')
                    ->where('url_certificate', '!=', '')
                    ->where('url_certificate', '!=', '-');
            }

            // Apply course filter
            $courseFilter = request()->input('course_filter');
            if ($courseFilter) {
                $query->where('course_id', $courseFilter);
            }

            // Apply date range filter
            $dateFrom = request()->input('date_from');
            $dateTo = request()->input('date_to');
            if ($dateFrom) {
                $query->whereDate('finished_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('finished_at', '<=', $dateTo);
            }

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('name', fn($row) => $row->participant?->name ?? '-')
                ->addColumn('satker', fn($row) => get_user_meta($row->participant_id, 'nama_jabatan') ?: '-')
                ->addColumn('course', fn($row) => $row->course?->title ?? '-')
                ->editColumn('finished_at', fn($row) => $row->finished_at ? $row->finished_at->format('d/m/Y') : '-')
                ->addColumn('status', function ($row) {
                    if ($row->url_certificate && $row->url_certificate !== '-' && $row->url_certificate !== '') {
                        return '<span class="badge bg-success">Sertifikat Tersedia</span>';
                    } elseif ($row->is_post_certificate) {
                        return '<span class="badge bg-info text-dark">Dikirim ke SSO</span>';
                    }
                    return '<span class="badge bg-warning text-dark">Menunggu Sync</span>';
                })
                ->addColumn('url_certificate', function ($row) {
                    $html = '<div class="d-flex justify-content-center gap-1">';
                    if ($row->url_certificate && $row->url_certificate !== '-' && $row->url_certificate !== '') {
                        $html .= '<a href="' . e($row->url_certificate) . '" target="_blank" class="btn btn-sm btn-primary" title="Download Sertifikat">
                                    <i class="ti ti-download"></i> Download
                                </a>';
                    }
                    $html .= '</div>';
                    return $html;
                })
                ->filterColumn('name', function ($query, $keyword) {
                    $query->whereHas('participant', function ($q) use ($keyword) {
                        $q->where('name', 'like', "%{$keyword}%");
                    });
                })
                ->filterColumn('course', function ($query, $keyword) {
                    $query->whereHas('course', function ($q) use ($keyword) {
                        $q->where('title', 'like', "%{$keyword}%");
                    });
                })
                ->rawColumns(['status', 'url_certificate'])
                ->make(true);
        }

        // Get courses that have finished learns for the dropdown filter
        $courses = Course::whereHas('learns', function ($q) {
            $q->whereNotNull('finished_at');
        })->orderBy('title')->get(['id', 'title']);

        return view('admin.certificate.index', compact('courses'));
    }

    /**
     * Trigger push certificate manually dari admin UI.
     * Mendukung push per course_id jika dikirim.
     */
    public function syncPush()
    {
        // Cegah DebugBar menyisipkan HTML ke JSON response
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        try {
            $courseId = request()->input('course_id');
            $learnId  = request()->input('learn_id');

            $params = [];
            if ($learnId) {
                $params['--learn'] = $learnId;
            } elseif ($courseId) {
                $params['--course'] = $courseId;
            }

            // Push 1000+ peserta bisa lama (fallback per-NIP)
            set_time_limit(0);

            $exitCode = Artisan::call('app:post-certificate-command', $params);
            $output   = trim(Artisan::output());

            // Blok debug ringkas (copy-paste)
            $debug = null;
            if (preg_match('/=== POST_CERT_DEBUG_START ===.*=== POST_CERT_DEBUG_END ===/s', $output, $m)) {
                $debug = trim($m[0]);
            }

            $finalMessage = 'Periksa log server untuk detail.';
            if (preg_match('/Push selesai\.\s*(Berhasil:\s*\d+,\s*Gagal:\s*\d+.*)$/m', $output, $matches)) {
                $finalMessage = trim($matches[1]);
            } elseif (preg_match('/(Berhasil:\s*\d+,\s*Gagal:\s*\d+.*)/', $output, $matches)) {
                $finalMessage = trim($matches[1]);
            } elseif (str_contains($output, 'Tidak ada data yang perlu di-push')) {
                $finalMessage = 'Tidak ada data yang perlu di-push.';
            } elseif (str_contains($output, 'Gagal mengambil token')) {
                foreach (explode("\n", $output) as $line) {
                    if (str_contains($line, 'Gagal mengambil token')) {
                        $finalMessage = trim($line);
                        break;
                    }
                }
            } elseif ($output && strlen($output) < 300) {
                $finalMessage = $output;
            }

            // Kalau ada error sample di debug, tempel singkat ke message
            if ($debug && preg_match('/error\[0\]:\s*(.+)$/m', $debug, $em)) {
                $finalMessage .= ' | ' . trim($em[1]);
            }

            $payload = [
                'message' => ($exitCode !== 0 ? 'Sync Push selesai dengan error. ' : 'Sync Push berhasil. ') . $finalMessage,
                'debug' => $debug,
            ];

            return response()->json($payload, $exitCode !== 0 ? 422 : 200);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Sync Push gagal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Trigger pull (fetch URL) certificate manual dari admin UI.
     * Mendukung pull per course_id jika dikirim.
     */
    public function syncPull(CertificateSyncService $syncService)
    {
        // Cegah DebugBar menyisipkan HTML ke JSON response
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        try {
            $courseId   = request()->input('course_id');
            $learnId    = request()->input('learn_id');
            $onlyHasUrl = request()->boolean('only_has_url');

            $result = $syncService->syncPull($courseId, $learnId, $onlyHasUrl);

            return response()->json([
                'message' => $result['message'],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Sync Pull gagal: ' . $e->getMessage()], 500);
        }
    }
}
