<?php

namespace App\Services\Course\Import;

use App\Models\Course\Activity;
use App\Models\Course\Course;
use App\Models\Course\Interactive;
use App\Models\Course\Literature;
use App\Models\Course\Question;
use App\Models\Course\Question\QuestionItem;
use App\Models\Course\Question\QuestionOption;
use App\Models\Course\Question\QuestionVariant;
use App\Models\Course\Schedule;
use App\Models\Course\Task;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Orkestrasi impor kursus Moodle (.mbz) ke aplikasi.
 *
 * Alur:
 *  1. analyze()  — simpan tiap file ke area temporer (dapat token), parse,
 *                  kelompokkan per original_course_id, kembalikan preview.
 *  2. import()   — berdasarkan token + konfigurasi instruktur, buat Course
 *                  beserta aktivitas yang diratakan + prefix "[Modul X]".
 *
 * Struktur modul mengikuti Opsi B (ratakan + prefix) karena aplikasi tidak
 * punya konsep section/modul: semua aktivitas digabung jadi satu daftar.
 */
class MbzImportService
{
    const TMP_DIR = 'mbz_imports';

    protected MbzParser $parser;

    public function __construct(MbzParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Simpan & analisis beberapa file .mbz, kelompokkan per kursus asal.
     *
     * @param  UploadedFile[]  $uploadedFiles
     * @return array{groups: array, errors: array}
     */
    public function analyze(array $uploadedFiles): array
    {
        $dir = $this->tmpDir();

        $parsedFiles = [];
        $errors = [];

        foreach ($uploadedFiles as $uploaded) {
            $originalName = $uploaded->getClientOriginalName();
            $token = Str::uuid()->toString();
            $target = $dir . DIRECTORY_SEPARATOR . $token . '.mbz';

            $uploaded->move($dir, $token . '.mbz');

            try {
                $parsed = $this->parser->parse($target);
            } catch (\Throwable $e) {
                @unlink($target);
                $errors[] = "{$originalName}: {$e->getMessage()}";
                continue;
            }

            $parsed['token'] = $token;
            $parsed['original_name'] = $originalName;
            $parsedFiles[] = $parsed;
        }

        return [
            'groups' => $this->groupParsed($parsedFiles),
            'errors' => $errors,
        ];
    }

    /**
     * Analisis dari file .mbz yang sudah ada di disk server (jalur pintas
     * testing: tanpa unggah). Hanya aktif saat APP_DEBUG/local.
     *
     * @param  string[]  $localPaths  path absolut file .mbz di server
     */
    public function analyzeLocal(array $localPaths): array
    {
        $dir = $this->tmpDir();
        $parsedFiles = [];
        $errors = [];

        foreach ($localPaths as $src) {
            $originalName = basename($src);

            if (!is_file($src)) {
                $errors[] = "{$originalName}: file tidak ditemukan di server.";
                continue;
            }

            $token = Str::uuid()->toString();
            $target = $dir . DIRECTORY_SEPARATOR . $token . '.mbz';

            if (!@copy($src, $target)) {
                $errors[] = "{$originalName}: gagal menyalin file.";
                continue;
            }

            try {
                $parsed = $this->parser->parse($target);
            } catch (\Throwable $e) {
                @unlink($target);
                $errors[] = "{$originalName}: {$e->getMessage()}";
                continue;
            }

            $parsed['token'] = $token;
            $parsed['original_name'] = $originalName;
            $parsedFiles[] = $parsed;
        }

        return [
            'groups' => $this->groupParsed($parsedFiles),
            'errors' => $errors,
        ];
    }

    /**
     * Kelompokkan hasil parse per original_course_id (file parsial digabung).
     */
    protected function groupParsed(array $parsedFiles): array
    {
        $groups = [];
        foreach ($parsedFiles as $pf) {
            $cid = $pf['course']['original_course_id'] ?: ('_' . $pf['token']);

            if (!isset($groups[$cid])) {
                $groups[$cid] = [
                    'original_course_id' => $pf['course']['original_course_id'],
                    'fullname'           => $pf['course']['fullname'],
                    'shortname'          => $pf['course']['shortname'],
                    'moodle_release'     => $pf['course']['moodle_release'],
                    'modules'            => [],
                    'total_activities'   => 0,
                    'total_supported'    => 0,
                    'warnings'           => [],
                ];
            }

            $supportedCount = count(array_filter($pf['activities'], fn($a) => $a['supported']));

            $groups[$cid]['modules'][] = [
                'token'           => $pf['token'],
                'original_name'   => $pf['original_name'],
                'backup_filename' => $pf['course']['backup_filename'],
                'module_label'    => $this->moduleLabelFromActivities($pf['activities']),
                'activities'      => $pf['activities'],
            ];
            $groups[$cid]['total_activities'] += count($pf['activities']);
            $groups[$cid]['total_supported']  += $supportedCount;
            $groups[$cid]['warnings'] = array_merge($groups[$cid]['warnings'], $pf['warnings']);
        }

        return array_values($groups);
    }

    /**
     * Buat kursus dari token-token hasil analyze + konfigurasi instruktur.
     *
     * @param  int    $instructorId
     * @param  array  $tokens   Urutan modul (token file .mbz).
     * @param  array  $config   type, category, employee_status[], title,
     *                          short_description, description, jam_pelajaran,
     *                          expired, teaser_video, status, thumbnail(UploadedFile|null)
     * @return Course
     */
    public function import(int $instructorId, array $tokens, array $config): Course
    {
        if (empty($tokens)) {
            throw new RuntimeException('Tidak ada file .mbz yang dipilih untuk diimpor.');
        }

        // Pastikan semua file masih ada sebelum menyentuh database.
        $mbzPaths = [];
        foreach ($tokens as $token) {
            $path = $this->tokenPath($token);
            if (!is_file($path)) {
                throw new RuntimeException('Sesi impor kedaluwarsa, unggah ulang file .mbz.');
            }
            $mbzPaths[$token] = $path;
        }

        $thumbnail = $config['thumbnail'] ?? null;
        $thumbnailName = '';
        if ($thumbnail instanceof UploadedFile) {
            $thumbnailName = random_filename($thumbnail);
            $thumbnail->move(public_path(trim(Course::$directoryImage, '/')), $thumbnailName);
        }

        $course = Course::create([
            'category_id'       => $config['category'],
            'instructor_id'     => $instructorId,
            'type'              => $config['type'],
            'title'             => $config['title'],
            'slug'              => $this->uniqueSlug($config['title']),
            'short_description' => sanitize_plain_text($config['short_description'] ?? ''),
            'description'       => sanitize_html($config['description'] ?? ''),
            'thumbnail'         => $thumbnailName,
            'teaser_video'      => $config['teaser_video'] ?? null,
            'expired'           => $config['expired'] ?? null,
            'jam_pelajaran'     => $config['jam_pelajaran'] ?? null,
            'status'            => $config['status'] ?? 'draft',
        ]);

        $course->employeeStatus()->attach($config['employee_status']);

        $moduleIndex = 0;
        $position = 0; // urutan aktivitas lintas modul (jaga urutan Moodle)
        foreach ($tokens as $token) {
            $moduleIndex++;
            $parsed = $this->parser->parse($mbzPaths[$token]);
            // Label cadangan bila sebuah aktivitas tidak punya judul section.
            $fileLabel = $this->moduleLabelFromActivities($parsed['activities'], $moduleIndex);

            // Kumpulkan literature berkonten agar bisa diekstrak satu pass.
            $pendingLiterature = [];

            foreach ($parsed['activities'] as $activity) {
                if (!$activity['supported']) {
                    continue;
                }

                // Lewati kuis yang tidak punya soal di backup — di kursus terbit
                // pre-test kosong hanya membingungkan peserta.
                if (
                    in_array($activity['app_type'], ['pre-test', 'post-test'], true)
                    && empty($activity['questions'])
                ) {
                    continue;
                }

                // Prefix per-aktivitas berdasarkan section-nya sendiri, sehingga
                // satu file Full dengan banyak modul tetap berlabel tepat.
                $label = $this->sectionLabel($activity['section_title'] ?? null, $fileLabel);
                $title = "[{$label}] " . $activity['title'];

                $position += 10;
                $created = Activity::create([
                    'course_id' => $course->id,
                    'type'      => $activity['app_type'],
                    'title'     => Str::limit($title, 250, ''),
                    'value'     => 0,
                    'position'  => $position,
                    'timer'     => in_array($activity['app_type'], ['pre-test', 'post-test']) ? 0 : null,
                ]);

                if ($activity['app_type'] === 'literature' && !empty($activity['file'])) {
                    $pendingLiterature[] = ['activity' => $created, 'data' => $activity];
                } else {
                    // Buat baris anak: soal kuis bila tersedia, selainnya
                    // placeholder agar halaman edit/detail tidak error.
                    $this->createActivityChildren($created, $activity);
                }
            }

            // Ekstrak semua file konten modul ini dalam SATU pass tar.
            if (!empty($pendingLiterature)) {
                $this->importLiteratureContents($mbzPaths[$token], $pendingLiterature);
            }
        }

        // Kursus wajib punya minimal satu schedule (lihat CourseService::createCourse).
        Schedule::create(['course_id' => $course->id]);

        // Bersihkan file temporer setelah impor sukses.
        foreach ($mbzPaths as $path) {
            @unlink($path);
        }

        return $course;
    }

    /**
     * Buat baris anak sesuai tipe aktivitas. Untuk pre-test/post-test, bila
     * soal berhasil diparse dari .mbz maka diisi otomatis; selainnya placeholder
     * agar halaman edit/detail tidak error (dilengkapi instruktur).
     */
    protected function createActivityChildren(Activity $activity, array $data): void
    {
        $note = 'Konten diimpor dari Moodle. Silakan lengkapi.';

        switch ($activity->type) {
            case 'pre-test':
            case 'post-test':
                if (!empty($data['questions'])) {
                    $this->createQuizContent($activity, $data);
                } else {
                    Question::create([
                        'activity_id' => $activity->id,
                        'description' => $note,
                        'type'        => $activity->type === 'post-test' ? 'post' : 'pre',
                        'min_score'   => 0,
                        'is_loop'     => 0,
                    ]);
                }
                break;

            case 'task':
                Task::create([
                    'activity_id' => $activity->id,
                    'description' => $note,
                ]);
                break;

            case 'meeting':
                Interactive::create([
                    'activity_id' => $activity->id,
                    'type'        => 'meeting',
                    'description' => $note,
                    'url'         => '#',
                    'duration'    => 0,
                ]);
                break;

            case 'literature':
                // Literature tanpa file terekstrak: buat placeholder kosong.
                Literature::create([
                    'activity_id' => $activity->id,
                    'sub_title'   => Str::limit($activity->title, 250, ''),
                    'duration'    => 0,
                    'content'     => '',
                ]);
                break;
        }
    }

    /**
     * Isi pre-test/post-test dengan soal pilihan ganda hasil parse .mbz.
     * Struktur: Question -> QuestionVariant -> QuestionItem -> QuestionOption.
     */
    protected function createQuizContent(Activity $activity, array $data): void
    {
        $intro = !empty($data['intro'])
            ? Str::limit($data['intro'], 1000, '')
            : 'Kuis diimpor dari Moodle.';

        $question = Question::create([
            'activity_id' => $activity->id,
            'description' => $intro,
            'type'        => $activity->type === 'post-test' ? 'post' : 'pre',
            'min_score'   => 0,
            'is_loop'     => 0,
        ]);

        $variant = QuestionVariant::create([
            'question_id' => $question->id,
            'code'        => 'Impor Moodle',
            'variant'     => 1,
            'mix'         => 1, // acak urutan (mengikuti shuffleanswers Moodle)
        ]);

        foreach ($data['questions'] as $q) {
            $item = QuestionItem::create([
                'question_variant_id' => $variant->id,
                'description'         => $q['question'],
                'score'               => 1,
            ]);

            foreach ($q['options'] as $opt) {
                QuestionOption::create([
                    'question_item_id' => $item->id,
                    'option'           => $opt['text'],
                    'is_correct'       => !empty($opt['correct']) ? 1 : 0,
                ]);
            }
        }
    }

    /**
     * Ekstrak konten beberapa literature sekaligus (satu pass tar) lalu buat
     * baris Literature. $pending = [ ['activity'=>Activity, 'data'=>arr], ... ].
     */
    protected function importLiteratureContents(string $mbzPath, array $pending): void
    {
        $dir = public_path(trim(Literature::$directoryDocument, '/'));
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $hashes = array_map(fn($p) => $p['data']['file']['contenthash'], $pending);
        $extractDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mbz_content_' . bin2hex(random_bytes(6));

        // Satu pass: ekstrak semua blob konten modul ini.
        $extracted = $this->parser->extractFiles($mbzPath, $hashes, $extractDir);

        foreach ($pending as $p) {
            $file = $p['data']['file'];
            $hash = $file['contenthash'];
            if (!isset($extracted[$hash])) {
                continue; // gagal ekstrak: literature dibuat tanpa file
            }

            $ext = strtolower(pathinfo($file['filename'], PATHINFO_EXTENSION) ?: 'pdf');
            $storedName = generate_unique_string(16) . '.' . $ext;

            if (!@rename($extracted[$hash], $dir . DIRECTORY_SEPARATOR . $storedName)) {
                continue;
            }

            Literature::create([
                'activity_id' => $p['activity']->id,
                'sub_title'   => Str::limit($p['data']['title'], 250, ''),
                'duration'    => 0,
                'content'     => $storedName,
            ]);
        }

        // Bersihkan direktori ekstrak sementara.
        if (is_dir($extractDir)) {
            $items = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($extractDir, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($items as $item) {
                $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
            }
            @rmdir($extractDir);
        }
    }

    /**
     * Turunkan label modul dari judul section (mis. "Modul 1: ...") bila ada.
     */
    protected function moduleLabelFromActivities(array $activities, int $fallbackIndex = 1): string
    {
        foreach ($activities as $a) {
            $label = $this->sectionLabel($a['section_title'] ?? null, null);
            if ($label !== null) {
                return $label;
            }
        }
        return 'Modul ' . $fallbackIndex;
    }

    /**
     * Label dari sebuah judul section: "Modul X" bila terdeteksi, selainnya
     * judul section yang dipendekkan. Kembalikan $fallback bila section kosong.
     */
    protected function sectionLabel(?string $section, ?string $fallback): ?string
    {
        $section = $section !== null ? trim($section) : '';
        if ($section === '') {
            return $fallback;
        }
        // Ambil "Modul N"/"Module N" bila judul diawali itu.
        if (preg_match('/^((?:Modul|Module)\s*\d+)/iu', $section, $m)) {
            return trim($m[1]);
        }
        return Str::limit($section, 30, '');
    }

    protected function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'kursus';
        $slug = $base;
        $i = 1;
        // Sertakan data terhapus (soft delete) karena slug ber-unique index
        // di level DB — baris trashed masih menempati slug tersebut.
        while (Course::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }

    protected function tmpDir(): string
    {
        $dir = storage_path('app' . DIRECTORY_SEPARATOR . self::TMP_DIR);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    protected function tokenPath(string $token): string
    {
        // Cegah path traversal: token harus UUID.
        if (!preg_match('/^[0-9a-fA-F-]{36}$/', $token)) {
            throw new RuntimeException('Token file tidak valid.');
        }
        return $this->tmpDir() . DIRECTORY_SEPARATOR . $token . '.mbz';
    }
}
