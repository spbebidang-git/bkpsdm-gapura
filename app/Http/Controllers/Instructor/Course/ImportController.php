<?php

namespace App\Http\Controllers\Instructor\Course;

use App\Models\Course\Category;
use App\Models\EmployeeStatus;
use App\Services\Course\Import\MbzImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ImportController
{
    protected MbzImportService $import;
    protected int $instructorId;

    public function __construct(MbzImportService $import)
    {
        $this->import = $import;
        $this->instructorId = Auth::user()->id;
    }

    /** Halaman wizard impor .mbz. */
    public function index()
    {
        return Inertia::render('Instructor/Course/Import', [
            'employee_status' => EmployeeStatus::all(),
            'categories'      => Category::all(),
        ]);
    }

    /** Langkah 1: unggah & analisis file .mbz, kembalikan preview JSON. */
    public function analyze(Request $request)
    {
        $request->validate([
            'files'   => 'required|array|min:1|max:20',
            'files.*' => 'required|file|max:1048576', // maks 1GB/file
        ]);

        $files = $request->file('files');

        foreach ($files as $file) {
            $ext = strtolower($file->getClientOriginalExtension());
            if ($ext !== 'mbz') {
                return response()->json([
                    'message' => "File \"{$file->getClientOriginalName()}\" bukan file Moodle Backup (.mbz).",
                ], 422);
            }
        }

        try {
            $result = $this->import->analyze($files);
        } catch (\Throwable $th) {
            Log::error('MBZ analyze failed: ' . $th->getMessage(), ['trace' => $th->getTraceAsString()]);
            return response()->json([
                'message' => 'Gagal menganalisis file: ' . $th->getMessage(),
            ], 500);
        }

        return response()->json($result);
    }

    /**
     * Jalur pintas (testing): analisis dari file .mbz contoh yang sudah ada di
     * disk server, tanpa unggah. Hanya aktif di environment lokal/debug.
     */
    public function analyzeLocal(Request $request)
    {
        if (!app()->isLocal() && !config('app.debug')) {
            abort(403, 'Hanya tersedia di mode pengembangan.');
        }

        $samples = $this->localSamples();
        $key = $request->input('key', 'full');

        if (!isset($samples[$key])) {
            return response()->json(['message' => 'File contoh tidak dikenal.'], 422);
        }

        try {
            $result = $this->import->analyzeLocal($samples[$key]);
        } catch (\Throwable $th) {
            Log::error('MBZ analyzeLocal failed: ' . $th->getMessage(), ['trace' => $th->getTraceAsString()]);
            return response()->json(['message' => 'Gagal menganalisis file contoh: ' . $th->getMessage()], 500);
        }

        return response()->json($result);
    }

    /** Daftar file .mbz contoh di disk (untuk jalur pintas testing). */
    protected function localSamples(): array
    {
        $downloads = 'C:/Users/bidsp/Downloads';

        return [
            'full'   => [$downloads . '/File Moodle Uji Coba ASN Berintegritas (Full).mbz'],
            'modul1' => [$downloads . '/[Modul 1] File Moodle Uji Coba.mbz'],
        ];
    }

    /** Langkah akhir: buat kursus dari token + konfigurasi instruktur. */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tokens'            => 'required|array|min:1',
            'tokens.*'          => 'string',
            'type'              => 'required|in:schedule,unschedule',
            'category'          => 'required|numeric|exists:categories,id',
            'employee_status'   => 'required|array|min:1',
            'employee_status.*' => 'exists:employee_status,id',
            'title'             => 'required|string|max:250',
            'short_description' => 'required|string',
            'description'       => 'required|string',
            'jam_pelajaran'     => 'required|string',
            'expired'           => 'required_if:type,unschedule|nullable|string',
            'teaser_video'      => 'nullable|string',
            'status'            => 'required|in:draft,publish',
            'thumbnail'         => 'nullable|image|max:10240|mimes:png,jpg,jpeg',
        ]);

        try {
            DB::beginTransaction();

            $config = $validated;
            $config['thumbnail'] = $request->file('thumbnail');

            $course = $this->import->import(
                $this->instructorId,
                $request->input('tokens'),
                $config
            );

            DB::commit();

            return to_route('instructor.course.show', $course->slug)
                ->with('success', 'Kursus berhasil diimpor dari file Moodle.');
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('MBZ import failed: ' . $th->getMessage(), ['trace' => $th->getTraceAsString()]);
            return redirect()->back()->withInput()
                ->with('danger', 'Gagal mengimpor kursus: ' . $th->getMessage());
        }
    }
}
