<?php

namespace App\Http\Controllers\Admin;

use App\Services\Integration\CategorySyncService;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class CategoryController
{
    public function index(CategorySyncService $categories)
    {
        if (request()->ajax() && ! request()->header('X-Inertia')) {
            $query = $categories->adminTable();

            return DataTables::of($query)
                ->editColumn('synced_at', fn ($row) => $row->synced_at?->format('d/m/Y H:i') ?? '-')
                ->only(['code', 'title', 'synced_at', 'updated_at'])
                ->make();
        }

        return Inertia::render('Admin/Category/Index', [
            'lastSyncedAt' => get_setting_meta('categories_last_synced_at') ?: null,
            'canSync' => request()->user()->hasRole('super_admin') || request()->user()->can('category.manage'),
        ]);
    }

    public function sync(CategorySyncService $sync)
    {
        try {
            $result = $sync->sync();

            return back()->with('success', $result['message']);
        } catch (\Throwable $e) {
            report($e);

            return back()->with('danger', 'Sinkronisasi kategori dari Prestige gagal. Silakan coba kembali atau hubungi administrator.');
        }
    }
}
