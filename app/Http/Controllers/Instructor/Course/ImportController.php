<?php

namespace App\Http\Controllers\Instructor\Course;

use App\Models\Course\Category;
use App\Models\EmployeeStatus;
use App\Services\Course\Import\MbzImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
            'local_samples'   => $this->serverImportEnabled()
                ? collect($this->localSamples())
                    ->map(fn ($sample, $key) => [
                        'key'   => $key,
                        'label' => $sample['label'],
                        'files' => count($sample['paths']),
                    ])
                    ->values()
                : [],
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
        if (!$this->serverImportEnabled()) {
            abort(403, 'Impor dari folder server tidak diaktifkan.');
        }

        $samples = $this->localSamples();
        $key = $request->input('key', 'full');

        if (!isset($samples[$key])) {
            return response()->json(['message' => 'File contoh tidak dikenal.'], 422);
        }

        try {
            $result = $this->import->analyzeLocal($samples[$key]['paths']);
        } catch (\Throwable $th) {
            Log::error('MBZ analyzeLocal failed: ' . $th->getMessage(), ['trace' => $th->getTraceAsString()]);
            return response()->json(['message' => 'Gagal menganalisis file contoh: ' . $th->getMessage()], 500);
        }

        return response()->json($result);
    }

    /**
     * Daftar file .mbz contoh di disk (untuk jalur pintas testing).
     * Hanya file yang benar-benar ada yang dikembalikan, jadi pintasan tidak
     * pernah menampilkan file yang sudah dihapus/pindah.
     *
     * @return array<string, array{label: string, paths: string[]}>
     */
    protected function localSamples(): array
    {
        $samples = [];

        $aset = rtrim(config('mbz.sample_dir') ?: '', '/\\');

        if ($aset !== '') {
            // 1) Paket full (satu file berisi seluruh modul).
            foreach (glob($aset . '/*.mbz') ?: [] as $file) {
                $samples['aset-' . Str::slug(pathinfo($file, PATHINFO_FILENAME))] = [
                    'label' => $this->sampleLabel($file),
                    'paths' => [$file],
                ];
            }

            // 2) Versi parsial per modul: satu tombol gabungan + per file.
            $perModul = glob($aset . '/Versi Per Modul/*.mbz') ?: [];
            sort($perModul);

            if (count($perModul) > 1) {
                $samples['aset-per-modul'] = [
                    'label' => 'Versi Per Modul - gabung semua (' . count($perModul) . ' file, '
                        . $this->humanSize(array_sum(array_map('filesize', $perModul))) . ')',
                    'paths' => $perModul,
                ];
            }

            foreach ($perModul as $file) {
                $samples['aset-modul-' . Str::slug(pathinfo($file, PATHINFO_FILENAME))] = [
                    'label' => $this->sampleLabel($file),
                    'paths' => [$file],
                ];
            }
        }

        return array_filter(
            $samples,
            fn ($sample) => count(array_filter($sample['paths'], 'is_file')) === count($sample['paths'])
        );
    }

    /**
     * Impor dari folder .mbz milik server aktif bila foldernya dikonfigurasi
     * dan benar-benar ada. Aksesnya sendiri sudah dibatasi middleware
     * instruktur pada grup route, jadi tidak lagi bergantung APP_DEBUG.
     */
    protected function serverImportEnabled(): bool
    {
        if (!config('mbz.server_import')) {
            return false;
        }

        $dir = (string) config('mbz.sample_dir');

        return $dir !== '' && is_dir($dir);
    }

    /** Label tombol pintasan: nama file dirapikan + ukuran. */
    protected function sampleLabel(string $file): string
    {
        $name = pathinfo($file, PATHINFO_FILENAME);

        // Rapikan pola nama ekspor Moodle, mis.
        // "cadangan-asn_berintegritas_2026-Modul_1-28072026-1027" → "Modul 1".
        if (Str::startsWith(strtolower($name), 'cadangan-')) {
            $name = preg_replace('/^cadangan-(moodle2-course-\d+-)?/i', '', $name);
            $name = preg_replace('/-\d{8}-\d{4}.*$/', '', $name);

            // Sisa format "<shortname kursus>-<judul bagian>": ambil judulnya saja.
            if (str_contains($name, '-')) {
                $name = Str::after($name, '-');
            }
        }

        $name = trim(str_replace('_', ' ', $name));

        $size = is_file($file) ? ' (' . $this->humanSize(filesize($file)) . ')' : '';

        return ($name !== '' ? $name : basename($file)) . $size;
    }

    protected function humanSize(int $bytes): string
    {
        return $bytes >= 1073741824
            ? round($bytes / 1073741824, 1) . ' GB'
            : round($bytes / 1048576) . ' MB';
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
