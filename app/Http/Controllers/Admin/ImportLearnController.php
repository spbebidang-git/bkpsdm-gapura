<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ImportLearnStoreRequest;
use App\Services\Learning\AdminLearnImportService;
use Inertia\Inertia;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Facades\Excel;

class ImportLearnController
{
    public function __construct(private readonly AdminLearnImportService $imports) {}

    public function index()
    {
        return Inertia::render('Admin/ImportLearn/Index', $this->imports->indexData(auth()->id()));
    }

    public function store(ImportLearnStoreRequest $request)
    {
        try {
            $this->imports->queue($request->file('file'), $request->validated(), auth()->id(), $request->boolean('create_missing_users'));

            return back()->with('success', 'Import masuk antrian. Refresh beberapa saat untuk hasil.');
        } catch (\Throwable $th) {
            report($th);

            return back()->with('danger', 'Impor gagal diproses. Periksa log menggunakan ID permintaan.');
        }
    }

    public function downloadTemplate()
    {
        return Excel::download(new class implements FromArray, WithHeadings
        {
            public function array(): array
            {
                return [
                    ['123456789012345678', 'Budi Santoso'],
                    ['987654321098765432', 'Siti Aminah'],
                ];
            }

            public function headings(): array
            {
                return ['nip', 'nama'];
            }
        }, 'template_import_learn.xlsx');
    }
}
