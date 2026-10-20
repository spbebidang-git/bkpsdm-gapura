<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CertificatesExport;
use App\Models\Course\Course;
use App\Models\Learning\Learn;
use App\Services\Integration\CertificateSyncService;
use Illuminate\Support\Facades\Artisan;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class CertificateController
{
    public function index()
    {
        if (request()->ajax() && ! request()->header('X-Inertia')) {
            if (app()->bound('debugbar')) {
                app('debugbar')->disable();
            }

            $query = $this->filteredQuery();

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('name', fn ($row) => $row->participant?->name ?? '-')
                ->addColumn('course', fn ($row) => $row->course?->title ?? '-')
                ->editColumn('finished_at', fn ($row) => $row->finished_at ? $row->finished_at->format('d/m/Y') : '-')
                ->addColumn('status_code', function ($row) {
                    if ($row->url_certificate && $row->url_certificate !== '-' && $row->url_certificate !== '') {
                        return 'available';
                    }
                    if ($row->is_post_certificate) {
                        return 'sent';
                    }

                    return 'pending';
                })
                ->addColumn('status_label', function ($row) {
                    if ($row->url_certificate && $row->url_certificate !== '-' && $row->url_certificate !== '') {
                        return 'Tersedia';
                    }
                    if ($row->is_post_certificate) {
                        return 'Tidak tersedia — Dikirim SSO';
                    }

                    return 'Tidak tersedia — Menunggu';
                })
                ->editColumn('url_certificate', function ($row) {
                    if ($row->url_certificate && $row->url_certificate !== '-' && $row->url_certificate !== '') {
                        return $row->url_certificate;
                    }

                    return '';
                })
                ->filterColumn('name', function ($query, $keyword) {
                    $query->whereHas('participant', fn ($q) => $q->where('name', 'like', "%{$keyword}%"));
                })
                ->orderColumn('name', false)
                ->orderColumn('course', false)
                ->filterColumn('course', function ($query, $keyword) {
                    $query->whereHas('course', fn ($q) => $q->where('title', 'like', "%{$keyword}%"));
                })
                ->make(true);
        }

        $courses = Course::whereHas('learns', fn ($q) => $q->whereNotNull('finished_at'))
            ->orderBy('title')
            ->get(['id', 'title']);

        return Inertia::render('Admin/Certificate/Index', [
            'courses' => $courses,
        ]);
    }

    private function filteredQuery()
    {
        request()->validate([
            'status_filter' => 'nullable|in:menunggu,dikirim,tersedia,tidak_tersedia',
            'course_filter' => 'nullable|string',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => ['nullable', 'date_format:Y-m-d', ...(request()->filled('date_from') ? ['after_or_equal:date_from'] : [])],
            'q' => 'nullable|string|max:255',
        ]);
        $query = Learn::query()
            ->with(['course', 'participant'])
            ->whereNotNull('finished_at')
            ->latest('finished_at')
            ->latest('id')
            ->select('learns.*');

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
        } elseif ($statusFilter === 'tidak_tersedia') {
            $query->where(fn ($q) => $q->whereNull('url_certificate')->orWhere('url_certificate', '')->orWhere('url_certificate', '-'));
        } elseif ($statusFilter === 'tersedia') {
            $query->whereNotNull('url_certificate')
                ->where('url_certificate', '!=', '')
                ->where('url_certificate', '!=', '-');
        }

        if ($courseFilter = request()->input('course_filter')) {
            $query->where('course_id', $courseFilter);
        }
        if ($dateFrom = request()->input('date_from')) {
            $query->whereDate('finished_at', '>=', $dateFrom);
        }
        if ($dateTo = request()->input('date_to')) {
            $query->whereDate('finished_at', '<=', $dateTo);
        }

        if ($search = trim((string) request()->input('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('participant', fn ($p) => $p->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('course', fn ($c) => $c->where('title', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    public function export()
    {
        return Excel::download(
            new CertificatesExport($this->filteredQuery()),
            'data-sertifikat-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }

    public function syncPush()
    {
        try {
            $courseId = request()->input('course_id');
            $learnId = request()->input('learn_id');

            $params = [];
            if ($learnId) {
                $params['--learn'] = $learnId;
            } elseif ($courseId) {
                $params['--course'] = $courseId;
            }

            set_time_limit(0);
            $exitCode = Artisan::call('app:post-certificate-command', $params);
            $output = trim(Artisan::output());

            $finalMessage = 'Periksa log server untuk detail.';
            if (preg_match('/Push selesai\.\s*(Berhasil:\s*\d+,\s*Skip:\s*\d+,\s*Gagal:\s*\d+.*)$/m', $output, $matches)) {
                $finalMessage = trim($matches[1]);
            } elseif (preg_match('/(Berhasil:\s*\d+,\s*Skip:\s*\d+,\s*Gagal:\s*\d+.*)/', $output, $matches)) {
                $finalMessage = trim($matches[1]);
            } elseif (str_contains($output, 'Tidak ada data yang perlu di-push')) {
                $finalMessage = 'Tidak ada data yang perlu di-push.';
            }

            $msg = ($exitCode !== 0 ? 'Sync Push error. ' : 'Sync Push OK. ').$finalMessage;

            if (request()->expectsJson() && ! request()->header('X-Inertia')) {
                return response()->json(['message' => $msg], $exitCode !== 0 ? 422 : 200);
            }

            return back()->with($exitCode !== 0 ? 'danger' : 'success', $msg);
        } catch (\Throwable $e) {
            report($e);
            if (request()->expectsJson() && ! request()->header('X-Inertia')) {
                return response()->json(['message' => 'Sync Push gagal. Silakan periksa log menggunakan ID permintaan.'], 500);
            }

            return back()->with('danger', 'Sync Push gagal. Silakan periksa log menggunakan ID permintaan.');
        }
    }

    public function syncPull(CertificateSyncService $syncService)
    {
        try {
            $result = $syncService->syncPull(
                request()->input('course_id'),
                request()->input('learn_id'),
                request()->boolean('only_has_url')
            );

            if (request()->expectsJson() && ! request()->header('X-Inertia')) {
                return response()->json(['message' => $result['message']]);
            }

            return back()->with('success', $result['message']);
        } catch (\Throwable $e) {
            report($e);
            if (request()->expectsJson() && ! request()->header('X-Inertia')) {
                return response()->json(['message' => 'Sync Pull gagal. Silakan periksa log menggunakan ID permintaan.'], 500);
            }

            return back()->with('danger', 'Sync Pull gagal. Silakan periksa log menggunakan ID permintaan.');
        }
    }
}
