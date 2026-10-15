<?php

namespace App\Http\Controllers\Admin;

use App\Models\Identity\Opd;
use App\Services\Identity\OpdService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

    public function create()
    {
        return Inertia::render('Admin/Opd/Form', ['opd' => null]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100', 'unique:opds,code'],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ], [], ['name' => 'Nama OPD/Unit Kerja']);

        $this->opds->createOpd($validated);

        return to_route('admin.opd.index')->with('success', 'OPD berhasil ditambahkan.');
    }

    public function edit(Opd $opd)
    {
        return Inertia::render('Admin/Opd/Form', ['opd' => $opd]);
    }

    public function update(Request $request, Opd $opd)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100', Rule::unique('opds', 'code')->ignore($opd->id)],
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ], [], ['name' => 'Nama OPD/Unit Kerja']);

        $this->opds->updateOpd($opd, $validated);

        return to_route('admin.opd.index')->with('success', 'OPD berhasil diperbarui.');
    }

    public function destroy(Opd $opd)
    {
        $this->opds->deleteOpd($opd);

        return to_route('admin.opd.index')->with('success', 'OPD berhasil dihapus.');
    }

    public function sync()
    {
        try {
            return to_route('admin.opd.index')->with('success', $this->opds->syncOpds()['message']);
        } catch (\Throwable $e) {
            return to_route('admin.opd.index')->with('danger', $e->getMessage());
        }
    }
}
