<?php

namespace App\Http\Controllers\Admin;

use App\Jobs\ImportLearnJob;
use App\Models\Course\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

class ImportLearnController
{
    public function index()
    {
        $courses = Course::where('type', 'schedule')
            ->where('status', 'publish')
            ->orderBy('title')
            ->get(['id', 'title']);

        // Hasil job async (jika sudah selesai)
        if ($cached = Cache::pull('import_learn:' . auth()->id())) {
            session()->flash('import_results', $cached['results']);
            session()->flash('import_course', $cached['course']);
        }

        return view('admin.import-learn.index', compact('courses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'finished_at' => 'required|date',
            'file' => 'required|file|max:5120',
            'create_missing_users' => 'nullable|boolean',
        ], [
            'course_id.required' => 'Pilih kursus terlebih dahulu.',
            'course_id.exists' => 'Kursus tidak ditemukan.',
            'finished_at.required' => 'Tanggal selesai harus diisi.',
            'finished_at.date' => 'Format tanggal tidak valid.',
            'file.required' => 'File Excel harus diupload.',
            'file.max' => 'Ukuran file maksimal 5MB.',
        ]);

        try {
            $path = $request->file('file')->store('imports');

            ImportLearnJob::dispatch(
                $path,
                $request->course_id,
                $request->finished_at,
                auth()->id(),
                $request->boolean('create_missing_users')
            );

            return back()->with('success', 'Import masuk antrian. Refresh halaman ini beberapa saat untuk melihat hasil.');
        } catch (\Throwable $th) {
            return back()->with('danger', 'Terjadi kesalahan: ' . $th->getMessage());
        }
    }

    public function downloadTemplate()
    {
        return Excel::download(new class implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
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
