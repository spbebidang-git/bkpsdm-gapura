<?php

namespace App\Http\Controllers\Admin;

use App\Imports\LearnBulkImport;
use App\Models\Course\Course;
use Illuminate\Http\Request;

use Maatwebsite\Excel\Facades\Excel;

class ImportLearnController
{
    public function index()
    {
        $courses = Course::where('type', 'schedule')
            ->where('status', 'publish')
            ->orderBy('title')
            ->get(['id', 'title']);

        return view('admin.import-learn.index', compact('courses'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,id',
            'finished_at' => 'required|date',
            'file' => 'required|file|max:5120',
        ], [
            'course_id.required' => 'Pilih kursus terlebih dahulu.',
            'course_id.exists' => 'Kursus tidak ditemukan.',
            'finished_at.required' => 'Tanggal selesai harus diisi.',
            'finished_at.date' => 'Format tanggal tidak valid.',
            'file.required' => 'File Excel harus diupload.',
            'file.max' => 'Ukuran file maksimal 5MB.',
        ]);

        try {
            $import = new LearnBulkImport(
                $request->course_id,
                $request->finished_at
            );

            Excel::import($import, $request->file('file'));

            $results = $import->getResults();

            $course = Course::find($request->course_id);

            return back()->with([
                'import_results' => $results,
                'import_course' => $course->title,
            ]);
        } catch (\Throwable $th) {
            return back()->with('danger', 'Terjadi kesalahan: ' . $th->getMessage());
        }
    }

    public function downloadTemplate()
    {
        // Generate a simple XLSX template using PhpSpreadsheet (via maatwebsite/excel)
        return Excel::download(new class implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\WithHeadings {
            public function array(): array
            {
                return [
                    ['123456789012345678'],
                    ['987654321098765432'],
                ];
            }

            public function headings(): array
            {
                return ['nip'];
            }
        }, 'template_import_learn.xlsx');
    }
}
