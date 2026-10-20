<?php

namespace App\Http\Controllers\Admin;

use App\Models\Identity\Opd;
use App\Services\Identity\OpdService;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class OpdController
{
    public function __construct(
        private readonly OpdService $opds,
    ) {}

    public function index()
    {
        if (request()->ajax() && ! request()->header('X-Inertia')) {
            return DataTables::of($this->opds->getOpdsForTable())
                ->addIndexColumn()
                ->editColumn('synced_at', fn (Opd $opd) => $opd->synced_at?->format('d/m/Y H:i') ?? null)
                ->make();
        }
        return Inertia::render('Admin/Opd/Index', $this->opds->getIndexData());
    }

    public function sync()
    {
        try {
            return to_route('admin.opd.index')->with('success', $this->opds->syncOpds()['message']);
        } catch (\Throwable $e) {
            report($e);

            return to_route('admin.opd.index')->with('danger', 'Sinkronisasi OPD dari Kedaton gagal. Silakan coba kembali atau hubungi administrator.');
        }
    }
}
