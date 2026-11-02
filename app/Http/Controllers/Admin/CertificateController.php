<?php

namespace App\Http\Controllers\Admin;

use App\Exports\CertificatesExport;
use App\Http\Requests\Admin\CertificateFilterRequest;
use App\Services\Integration\CertificateSyncService;
use App\Services\Reporting\AdminCertificateService;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class CertificateController
{
    public function __construct(private readonly AdminCertificateService $certificates) {}

    public function index(CertificateFilterRequest $request)
    {
        if (request()->ajax() && ! request()->header('X-Inertia')) {
            if (app()->bound('debugbar')) {
                app('debugbar')->disable();
            }

            $query = $this->certificates->query($request->validated());

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
                    $this->certificates->filterColumn($query, 'participant', 'name', $keyword);
                })
                ->orderColumn('name', false)
                ->orderColumn('course', false)
                ->filterColumn('course', function ($query, $keyword) {
                    $this->certificates->filterColumn($query, 'course', 'title', $keyword);
                })
                ->make(true);
        }

        $courses = $this->certificates->courses();

        return Inertia::render('Admin/Certificate/Index', [
            'courses' => $courses,
        ]);
    }

    public function export(CertificateFilterRequest $request)
    {
        return Excel::download(
            new CertificatesExport($this->certificates->query($request->validated())),
            'data-sertifikat-'.now()->format('Y-m-d-His').'.xlsx'
        );
    }

    public function syncPush()
    {
        try {
            ['exitCode' => $exitCode, 'message' => $msg] = $this->certificates->push(request()->input('course_id'), request()->input('learn_id'));

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
