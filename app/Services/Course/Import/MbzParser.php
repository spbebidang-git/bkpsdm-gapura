<?php

namespace App\Services\Course\Import;

use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Membaca file Moodle Backup (.mbz) — yang sebenarnya adalah arsip tar.gz —
 * dan mengubah metadata-nya menjadi struktur yang dimengerti aplikasi.
 *
 * Performa: metadata (moodle_backup.xml, files.xml, seluruh inforef.xml)
 * diekstrak dalam SATU kali pass tar (mengecualikan blob besar di files/),
 * lalu dibaca dari disk. Ini menghindari pemindaian ulang arsip untuk tiap
 * aktivitas yang pada file besar (ratusan MB) sangat lambat.
 */
class MbzParser
{
    /**
     * Pemetaan nama modul Moodle -> tipe aktivitas aplikasi.
     * null = belum/ tidak didukung (dilewati saat impor, diberi peringatan).
     */
    const TYPE_MAP = [
        'resource'        => 'literature',
        'page'            => 'literature',
        'book'            => 'literature',
        'url'             => 'literature',
        'folder'          => 'literature',
        'label'           => 'literature',
        'quiz'            => 'pre-test',
        'assign'          => 'task',
        'assignment'      => 'task',
        'questionnaire'   => 'questionnaire',
        'bigbluebuttonbn' => 'meeting',
        'zoom'            => 'meeting',
        'h5pactivity'     => 'h5p',
        'scorm'           => 'scorm',
        'forum'           => null,
        'choice'          => null,
        'feedback'        => null,
        'glossary'        => null,
        'wiki'            => null,
        'lesson'          => null,
        'workshop'        => null,
        'data'            => null,
        'lti'             => null,
    ];

    /** Alasan singkat kenapa sebuah modul belum didukung. */
    const UNSUPPORTED_REASON = [
        'forum'       => 'Forum diskusi tidak dipakai di aplikasi ini.',
        'choice'      => 'Aktivitas pilihan (choice) tidak dipakai.',
        'feedback'    => 'Aktivitas feedback tidak dipakai.',
        'glossary'    => 'Glosarium tidak dipakai.',
        'wiki'        => 'Wiki tidak dipakai.',
        'lesson'      => 'Aktivitas lesson belum didukung.',
        'workshop'    => 'Aktivitas workshop belum didukung.',
        'data'        => 'Database activity tidak dipakai.',
        'lti'         => 'Tautan LTI eksternal belum didukung.',
    ];

    /**
     * Tipe pertanyaan mod_questionnaire (type_id) -> label untuk instruktur.
     * Nomornya mengikuti konstanta QUES* plugin mod_questionnaire
     * (classes/question/question.php): id 7 memang tidak ada, rate = 8,
     * date = 9, numeric = 10. 99 = page break & 100 = teks seksi,
     * keduanya bukan pertanyaan.
     */
    const QUESTIONNAIRE_PAGEBREAK = 99;
    const QUESTIONNAIRE_SECTION   = 100;
    const QUESTIONNAIRE_TYPE = [
        1  => 'Ya/Tidak',
        2  => 'Isian singkat',
        3  => 'Esai',
        4  => 'Pilihan ganda',
        5  => 'Pilihan jamak',
        6  => 'Dropdown',
        8  => 'Skala penilaian',
        9  => 'Tanggal',
        10 => 'Angka',
    ];

    /**
     * type_id mod_questionnaire -> kunci tipe aktivitas Kuesioner aplikasi
     * (lihat QuestionnaireQuestion::TYPES). Tipe yang tak dikenal jadi 'text'.
     */
    const QUESTIONNAIRE_TYPE_KEY = [
        1  => 'yesno',
        2  => 'text',
        3  => 'essay',
        4  => 'radio',
        5  => 'check',
        6  => 'drop',
        8  => 'rate',
        9  => 'date',
        10 => 'numeric',
    ];

    /**
     * Modul yang bukan aktivitas peserta sehingga tidak perlu muncul di daftar
     * impor sama sekali. `qbank` (Moodle 4.3+) hanya wadah bank soal; isinya
     * sudah terbaca lewat questions.xml saat kuis diproses.
     */
    const HIDDEN_MODULES = ['qbank'];

    /** Pola judul kuis yang menandakan post-test. */
    const POST_TEST_PATTERN = '/\b(post[\s\-_]*test|pasca[\s\-_]*test|tes(?:t)?[\s\-_]*akhir|evaluasi[\s\-_]*akhir|ujian[\s\-_]*akhir)\b/iu';

    protected string $tarBin;

    public function __construct()
    {
        $this->tarBin = $this->resolveTarBinary();
    }

    /**
     * Baca metadata backup dari sebuah file .mbz.
     *
     * @return array{course: array, activities: array, warnings: array}
     */
    public function parse(string $mbzPath): array
    {
        if (!is_file($mbzPath)) {
            throw new RuntimeException("File .mbz tidak ditemukan: {$mbzPath}");
        }

        $work = $this->makeWorkDir();

        try {
            // Satu pass: ekstrak semua XML metadata, lewati blob besar files/.
            $this->extractMetadata($mbzPath, $work);

            $xmlPath = $work . DIRECTORY_SEPARATOR . 'moodle_backup.xml';
            if (!is_file($xmlPath)) {
                throw new RuntimeException('File bukan backup Moodle yang valid: moodle_backup.xml tidak ada.');
            }

            $backup = @simplexml_load_file($xmlPath);
            if ($backup === false || !isset($backup->information)) {
                throw new RuntimeException('moodle_backup.xml tidak dapat dibaca.');
            }

            $info = $backup->information;

            $course = [
                'original_course_id' => trim((string) $info->original_course_id),
                'fullname'           => trim((string) $info->original_course_fullname),
                'shortname'          => trim((string) $info->original_course_shortname),
                'moodle_release'     => trim((string) $info->moodle_release),
                'backup_filename'    => trim((string) $info->name),
            ];

            $sections = [];
            foreach ($info->contents->sections->section ?? [] as $section) {
                $sections[(string) $section->sectionid] = trim((string) $section->title);
            }

            // Index file (id -> info) dari files.xml yang sudah diekstrak.
            $files = $this->loadFilesIndex($work . DIRECTORY_SEPARATOR . 'files.xml');

            // Bank soal (kategori & entri -> daftar soal pilihan ganda).
            $questionBank = $this->loadQuestionBank($work);

            $activities = [];
            $warnings = [];

            foreach ($info->contents->activities->activity ?? [] as $activity) {
                $moduleName = (string) $activity->modulename;
                $title      = trim((string) $activity->title);
                $sectionId  = (string) $activity->sectionid;
                $directory  = (string) $activity->directory;

                // Bank soal bukan aktivitas peserta — soalnya sudah ikut
                // terbaca lewat kuis yang merujuknya, jadi tidak perlu
                // ditampilkan sebagai aktivitas yang "dilewati".
                if (in_array($moduleName, self::HIDDEN_MODULES, true)) {
                    continue;
                }

                $supported = array_key_exists($moduleName, self::TYPE_MAP)
                    ? self::TYPE_MAP[$moduleName] !== null
                    : false;

                $appType = self::TYPE_MAP[$moduleName] ?? null;

                // Kuis Moodle default ke pre-test; post-test dikenali dari judul.
                if ($appType === 'pre-test' && $this->looksLikePostTest($title)) {
                    $appType = 'post-test';
                }

                $note = null;
                if (!$supported) {
                    $note = self::UNSUPPORTED_REASON[$moduleName]
                        ?? "Modul '{$moduleName}' belum didukung.";
                    $warnings[] = "Dilewati: \"{$title}\" ({$moduleName}) — {$note}";
                } elseif ($moduleName === 'quiz') {
                    $note = 'Terdeteksi sebagai kuis — tinjau apakah pre-test atau post-test.';
                }

                $fileRef = null;
                if ($supported && $appType === 'literature') {
                    $fileRef = $this->resolveActivityFile($work, $directory, $files);
                    if ($fileRef !== null && $this->isVideoFile($fileRef)) {
                        $appType = 'video';
                        $note = 'File video ditemukan — akan diimpor sebagai Presentasi Video.';
                    }
                } elseif ($supported && $appType === 'scorm') {
                    $fileRef = $this->resolveActivityFile($work, $directory, $files, 'zip');
                    if ($fileRef !== null) {
                        $note = 'Paket SCORM ditemukan — akan diekstrak otomatis.';
                    } else {
                        $note = 'Paket SCORM tidak ada di backup — aktivitas tetap dibuat agar file .zip dapat langsung diunggah lewat Edit.';
                        $warnings[] = "Paket SCORM tidak ditemukan: \"{$title}\" — aktivitas upload akan tetap dibuat.";
                    }
                } elseif ($supported && $appType === 'h5p') {
                    $fileRef = $this->resolveActivityFile($work, $directory, $files, 'h5p');
                    if ($fileRef !== null) {
                        $note = 'Paket H5P ditemukan — akan dimuat otomatis.';
                    } else {
                        $note = 'Paket H5P tidak ada di backup — aktivitas tetap dibuat agar file .h5p dapat langsung diunggah lewat Edit.';
                        $warnings[] = "Paket H5P tidak ditemukan: \"{$title}\" — aktivitas upload akan tetap dibuat.";
                    }
                }

                // Soal kuis (otomatis mengisi pre-test/post-test).
                $questions = [];
                $intro = null;
                if ($supported && $moduleName === 'quiz') {
                    [$questions, $intro] = $this->resolveQuizQuestions($work, $directory, $questionBank);
                    if (!empty($questions)) {
                        $label = $appType === 'post-test' ? 'post-test' : 'pre-test';
                        $note = count($questions) . " soal terbaca — akan mengisi {$label} otomatis.";
                    }
                }

                // Kuesioner Moodle (mod_questionnaire) dipindahkan apa adanya
                // ke tipe aktivitas Kuesioner: peserta mengisinya di aplikasi.
                $formQuestions = [];
                $formAnonymous = false;
                if ($supported && $moduleName === 'questionnaire') {
                    [$formQuestions, $intro, $formAnonymous] = $this->resolveQuestionnaire($work, $directory);
                    $count = count(array_filter($formQuestions, fn ($q) => !$q['is_section']));
                    $note = $count > 0
                        ? "{$count} pertanyaan kuesioner terbaca — form-nya dibuat otomatis"
                            . ($formAnonymous ? ' (anonim).' : '.')
                        : 'Kuesioner tanpa pertanyaan di backup — pertanyaannya dilengkapi lewat Edit.';
                }

                $activities[] = [
                    'module_id'      => (string) $activity->moduleid,
                    'module_name'    => $moduleName,
                    'title'          => $title,
                    'section_id'     => $sectionId,
                    'section_title'  => $sections[$sectionId] ?? null,
                    'directory'      => $directory,
                    'app_type'       => $appType,
                    'supported'      => $supported,
                    'note'           => $note,
                    'file'           => $fileRef,
                    'intro'          => $intro,
                    'question_count' => count($questions),
                    'questions'      => $questions,
                    'form_questions' => $formQuestions,
                    'form_anonymous' => $formAnonymous,
                ];
            }

            return [
                'course'     => $course,
                'sections'   => $sections,
                'activities' => $activities,
                'warnings'   => $warnings,
            ];
        } finally {
            $this->removeDir($work);
        }
    }

    /**
     * Ekstrak beberapa file konten sekaligus (berdasarkan contenthash) dalam
     * SATU pass tar ke $destDir. Mengembalikan peta contenthash => path absolut
     * file hasil ekstrak (hanya yang berhasil).
     *
     * @param  string[]  $contenthashes
     * @return array<string,string>
     */
    public function extractFiles(string $mbzPath, array $contenthashes, string $destDir): array
    {
        $contenthashes = array_values(array_unique(array_filter($contenthashes)));
        if (empty($contenthashes)) {
            return [];
        }

        if (!is_dir($destDir)) {
            @mkdir($destDir, 0775, true);
        }

        $members = [];
        foreach ($contenthashes as $hash) {
            $members[] = 'files/' . substr($hash, 0, 2) . '/' . $hash;
        }

        $process = new Process(array_merge(
            [$this->tarBin, '-xf', $mbzPath, '-C', $destDir],
            $members
        ));
        $process->setTimeout(1800);
        $process->run();
        // Abaikan status: member yang tak ada bikin tar non-zero, tapi member
        // lain tetap terekstrak. Kita cek per-file di bawah.

        $result = [];
        foreach ($contenthashes as $hash) {
            $path = $destDir . DIRECTORY_SEPARATOR . 'files'
                . DIRECTORY_SEPARATOR . substr($hash, 0, 2)
                . DIRECTORY_SEPARATOR . $hash;
            if (is_file($path)) {
                $result[$hash] = $path;
            }
        }

        return $result;
    }

    /**
     * Ekstrak satu file konten ke path tujuan (dipakai untuk kasus tunggal).
     */
    public function extractFile(string $mbzPath, string $contenthash, string $destPath): bool
    {
        $member = 'files/' . substr($contenthash, 0, 2) . '/' . $contenthash;

        $process = new Process([$this->tarBin, '-xOf', $mbzPath, $member]);
        $process->setTimeout(600);
        $process->run();

        if (!$process->isSuccessful()) {
            return false;
        }

        return file_put_contents($destPath, $process->getOutput()) !== false;
    }

    /**
     * Ekstrak seluruh XML metadata dalam satu pass, mengecualikan blob files/.
     */
    protected function extractMetadata(string $mbzPath, string $destDir): void
    {
        $process = new Process([
            $this->tarBin,
            '-xf', $mbzPath,
            '-C', $destDir,
            '--exclude', 'files/*',
        ]);
        $process->setTimeout(1800);
        $process->run();

        if (!$process->isSuccessful() && !is_file($destDir . DIRECTORY_SEPARATOR . 'moodle_backup.xml')) {
            throw new RuntimeException('Gagal membaca arsip .mbz: ' . trim($process->getErrorOutput()));
        }
    }

    /**
     * Baca seluruh entri files.xml menjadi index id => metadata file.
     */
    protected function loadFilesIndex(string $filesXmlPath): array
    {
        if (!is_file($filesXmlPath)) {
            return [];
        }

        $doc = @simplexml_load_file($filesXmlPath);
        if ($doc === false) {
            return [];
        }

        $index = [];
        foreach ($doc->file ?? [] as $file) {
            $id = (string) $file['id'];
            $index[$id] = [
                'contenthash' => (string) $file->contenthash,
                'filename'    => (string) $file->filename,
                'filepath'    => (string) $file->filepath,
                'mimetype'    => (string) $file->mimetype,
                'filesize'    => (int) $file->filesize,
                'component'   => (string) $file->component,
                'filearea'    => (string) $file->filearea,
            ];
        }

        return $index;
    }

    /**
     * Cari file utama milik sebuah aktivitas (mis. PDF dari resource) lewat
     * inforef.xml yang sudah diekstrak -> daftar id file -> index files.xml.
     */
    protected function resolveActivityFile(
        string $workDir,
        string $directory,
        array $files,
        ?string $requiredExtension = null
    ): ?array
    {
        if ($directory === '' || empty($files)) {
            return null;
        }

        $inforefPath = $workDir . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $directory)
            . DIRECTORY_SEPARATOR . 'inforef.xml';

        if (!is_file($inforefPath)) {
            return null;
        }

        $doc = @simplexml_load_file($inforefPath);
        if ($doc === false || !isset($doc->fileref)) {
            return null;
        }

        $candidate = null;
        foreach ($doc->fileref->file ?? [] as $ref) {
            $id = (string) $ref->id;
            if (!isset($files[$id])) {
                continue;
            }
            $f = $files[$id];
            if ($f['filename'] === '' || $f['filename'] === '.' || $f['filesize'] <= 0) {
                continue;
            }
            if (
                $requiredExtension !== null
                && strtolower(pathinfo($f['filename'], PATHINFO_EXTENSION)) !== strtolower($requiredExtension)
            ) {
                continue;
            }
            if ($candidate === null || $f['filesize'] > $candidate['filesize']) {
                $candidate = $f;
            }
        }

        if ($candidate === null) {
            return null;
        }

        return [
            'contenthash' => $candidate['contenthash'],
            'filename'    => $candidate['filename'],
            'mimetype'    => $candidate['mimetype'],
            'filesize'    => $candidate['filesize'],
        ];
    }

    protected function isVideoFile(array $file): bool
    {
        $mimeType = strtolower((string) ($file['mimetype'] ?? ''));
        $extension = strtolower(pathinfo((string) ($file['filename'] ?? ''), PATHINFO_EXTENSION));

        return str_starts_with($mimeType, 'video/')
            || in_array($extension, ['mp4', 'webm', 'ogg', 'ogv', 'mov', 'm4v'], true);
    }

    /**
     * Baca questions.xml menjadi index bank soal pilihan ganda:
     * ['byCategory' => [catId => [soal...]], 'byEntry' => [entryId => soal]].
     * Tiap entri bank soal diambil VERSI TERTINGGI-nya saja.
     */
    protected function loadQuestionBank(string $workDir): array
    {
        $path = $workDir . DIRECTORY_SEPARATOR . 'questions.xml';
        if (!is_file($path)) {
            return ['byCategory' => [], 'byEntry' => []];
        }

        $doc = @simplexml_load_file($path);
        if ($doc === false) {
            return ['byCategory' => [], 'byEntry' => []];
        }

        $byCategory = [];
        $byEntry = [];

        foreach ($doc->question_category ?? [] as $cat) {
            $catId = (string) $cat['id'];

            foreach ($cat->question_bank_entries->question_bank_entry ?? [] as $entry) {
                $entryId = (string) $entry['id'];

                // Pilih versi tertinggi (status "ready" diutamakan).
                $bestVer = -1;
                $bestQuestion = null;
                foreach ($entry->question_version->question_versions ?? [] as $ver) {
                    $vnum = (int) $ver->version;
                    $status = (string) $ver->status;
                    if ($status !== '' && $status !== 'ready') {
                        continue;
                    }
                    if ($vnum > $bestVer && isset($ver->questions->question)) {
                        $bestVer = $vnum;
                        $bestQuestion = $ver->questions->question;
                    }
                }

                if ($bestQuestion === null || (string) $bestQuestion->qtype !== 'multichoice') {
                    continue;
                }

                $parsed = $this->parseMultichoice($bestQuestion);
                if ($parsed === null) {
                    continue;
                }

                $byCategory[$catId][] = $parsed;
                $byEntry[$entryId] = $parsed;
            }
        }

        return ['byCategory' => $byCategory, 'byEntry' => $byEntry];
    }

    /**
     * Ambil daftar soal untuk sebuah kuis + intro-nya.
     *
     * @return array{0: array, 1: ?string}  [questions, intro]
     */
    protected function resolveQuizQuestions(string $workDir, string $directory, array $bank): array
    {
        $path = $workDir . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $directory)
            . DIRECTORY_SEPARATOR . 'quiz.xml';

        if (!is_file($path)) {
            return [[], null];
        }

        $content = file_get_contents($path);

        $intro = null;
        $doc = @simplexml_load_string($content);
        if ($doc !== false && isset($doc->quiz->intro)) {
            $intro = $this->cleanText((string) $doc->quiz->intro) ?: null;
        }

        // Referensi soal spesifik (question_reference) diutamakan;
        // bila tidak ada, pakai kategori (question_set_reference / acak).
        $questions = [];

        preg_match_all('/<questionbankentryid>(\d+)<\/questionbankentryid>/', $content, $em);
        $entryIds = array_values(array_unique($em[1] ?? []));

        if (!empty($entryIds)) {
            foreach ($entryIds as $id) {
                if (isset($bank['byEntry'][$id])) {
                    $questions[] = $bank['byEntry'][$id];
                }
            }
        }

        if (empty($questions)) {
            preg_match_all('/"values":\[([0-9,]+)\]/', $content, $cm);
            $catIds = [];
            foreach ($cm[1] ?? [] as $csv) {
                foreach (explode(',', $csv) as $cid) {
                    $catIds[trim($cid)] = true;
                }
            }
            foreach (array_keys($catIds) as $cid) {
                if (isset($bank['byCategory'][$cid])) {
                    foreach ($bank['byCategory'][$cid] as $q) {
                        $questions[] = $q;
                    }
                }
            }
        }

        return [$questions, $intro];
    }

    /**
     * Moodle tidak menandai kuis sebagai pre/post, jadi judulnya yang dibaca:
     * "Post Test", "Tes Akhir", "Evaluasi Akhir", dst.
     */
    protected function looksLikePostTest(string $title): bool
    {
        return (bool) preg_match(self::POST_TEST_PATTERN, $title);
    }

    /**
     * Baca questionnaire.xml sebuah aktivitas kuesioner Moodle: daftar
     * pertanyaan beserta tipe, kewajiban, dan pilihan jawabannya, untuk dibuat
     * ulang sebagai aktivitas Kuesioner aplikasi.
     *
     * @return array{0: array, 1: ?string, 2: bool}  [questions, intro, isAnonymous]
     */
    protected function resolveQuestionnaire(string $workDir, string $directory): array
    {
        $path = $workDir . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $directory)
            . DIRECTORY_SEPARATOR . 'questionnaire.xml';

        if (!is_file($path)) {
            return [[], null, false];
        }

        $doc = @simplexml_load_file($path);
        if ($doc === false || !isset($doc->questionnaire)) {
            return [[], null, false];
        }

        $intro = $this->cleanText((string) $doc->questionnaire->intro) ?: null;

        // <respondenttype> Moodle: "anonymous" atau "fullname".
        $isAnonymous = strtolower(trim((string) $doc->questionnaire->respondenttype)) === 'anonymous';

        $questions = [];
        foreach ($doc->questionnaire->surveys->survey->questions->question ?? [] as $question) {
            $typeId = (int) $question->type_id;

            if ($typeId === self::QUESTIONNAIRE_PAGEBREAK) {
                continue;
            }

            $name = $this->questionnaireValue((string) $question->name);
            $text = $this->cleanText((string) $question->content);

            if ($text === '' && $name === '') {
                continue;
            }

            $typeKey = self::QUESTIONNAIRE_TYPE_KEY[$typeId] ?? 'text';

            $choices = [];
            foreach ($question->quest_choices->quest_choice ?? [] as $choice) {
                $value = $this->cleanText((string) $choice->content);
                if ($value !== '') {
                    $choices[] = $value;
                }
            }

            $questions[] = [
                'position'   => (int) $question->position,
                'name'       => $name,
                'text'       => $text !== '' ? $text : $name,
                'type'       => self::QUESTIONNAIRE_TYPE[$typeId] ?? 'Isian',
                'type_key'   => $typeKey,
                'length'     => $this->questionnaireLimit(
                    $typeKey,
                    (int) $question->length,
                    (int) $question->precise
                ),
                'required'   => strtolower($this->questionnaireValue((string) $question->required)) === 'y',
                'is_section' => $typeId === self::QUESTIONNAIRE_SECTION,
                'choices'    => $choices,
            ];
        }

        // Urutan di XML acak; tampilan Moodle mengikuti <position>.
        usort($questions, fn ($a, $b) => $a['position'] <=> $b['position']);

        return [$questions, $intro, $isAnonymous];
    }

    /**
     * Arti kolom <length> dan <precise> mod_questionnaire berbeda per tipe
     * (lihat classes/question/*.php), sedangkan aplikasi hanya menyimpan satu
     * angka batas per pertanyaan:
     *
     *   text    length = lebar kotak, precise = maksimal karakter  -> precise
     *   numeric length = maksimal digit, precise = jumlah desimal  -> length
     *   rate    length = jumlah tingkat skala                      -> length
     *   essay   length = jumlah baris, precise = format jawaban    -> 0 (bebas)
     *   lainnya tidak dipakai                                      -> 0
     */
    protected function questionnaireLimit(string $typeKey, int $length, int $precise): int
    {
        $limit = match ($typeKey) {
            'text'    => $precise,
            'numeric', 'rate' => $length,
            default   => 0,
        };

        return max(0, $limit);
    }

    /** Moodle menulis nilai kosong sebagai "$@NULL@$". */
    protected function questionnaireValue(string $value): string
    {
        $value = trim($value);

        return $value === '$@NULL@$' ? '' : $value;
    }

    /**
     * Ubah sebuah <question> multichoice menjadi struktur sederhana:
     * ['question' => teks, 'options' => [['text'=>..,'correct'=>bool], ...]].
     */
    protected function parseMultichoice(\SimpleXMLElement $q): ?array
    {
        $text = $this->cleanText((string) $q->questiontext);
        if ($text === '') {
            return null;
        }

        $options = [];
        $maxFraction = 0.0;
        foreach ($q->plugin_qtype_multichoice_question->answers->answer ?? [] as $a) {
            $atext = $this->cleanText((string) $a->answertext);
            if ($atext === '') {
                continue;
            }
            $fraction = (float) $a->fraction;
            $maxFraction = max($maxFraction, $fraction);
            $options[] = ['text' => $atext, 'fraction' => $fraction];
        }

        if (count($options) < 2) {
            return null; // bukan soal pilihan ganda yang valid
        }

        // Tandai satu jawaban benar: fraksi tertinggi yang positif (model radio).
        $correctAssigned = false;
        $final = [];
        foreach ($options as $opt) {
            $isCorrect = (!$correctAssigned && $maxFraction > 0 && $opt['fraction'] >= $maxFraction);
            if ($isCorrect) {
                $correctAssigned = true;
            }
            $final[] = ['text' => $opt['text'], 'correct' => $isCorrect];
        }

        return ['question' => $text, 'options' => $final];
    }

    /**
     * Bersihkan teks HTML Moodle: buang tag, decode entity, rapikan spasi.
     */
    protected function cleanText(string $html): string
    {
        $text = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);
        $text = preg_replace('/\s+/u', ' ', $text);
        return trim($text ?? '');
    }

    protected function makeWorkDir(): string
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mbz_meta_' . bin2hex(random_bytes(6));
        if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new RuntimeException('Gagal membuat direktori kerja sementara.');
        }
        return $dir;
    }

    protected function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }

    /**
     * Temukan biner tar yang dapat dijalankan dari PHP.
     */
    protected function resolveTarBinary(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $system32 = (getenv('SystemRoot') ?: 'C:\\Windows') . '\\System32\\tar.exe';
            if (is_file($system32)) {
                return $system32;
            }
        }
        return 'tar';
    }
}
