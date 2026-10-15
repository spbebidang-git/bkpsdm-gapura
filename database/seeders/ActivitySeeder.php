<?php

namespace Database\Seeders;

use App\Models\Course\Activity\Activity;
use App\Models\Course\Activity\Assessment\Question;
use App\Models\Course\Activity\Assessment\QuestionVariant;
use App\Models\Course\Activity\Assessment\QuestionItem;
use App\Models\Course\Activity\Assessment\QuestionOption;
use App\Models\Course\Course;
use App\Models\Course\Activity\Interactive;
use App\Models\Course\Activity\Literature;
use App\Models\Course\Activity\Task;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    /** Kegiatan lengkap; meeting dilewati utk kelas scheduled. */
    private array $activities = [
        ['type' => 'pre-test', 'title' => 'Pre-Test'],
        ['type' => 'video', 'title' => 'Video Presentasi'],
        ['type' => 'literature', 'title' => 'Literature'],
        ['type' => 'task', 'title' => 'Tugas'],
        ['type' => 'meeting', 'title' => 'Pertemuan Daring'],
        ['type' => 'post-test', 'title' => 'Post-Test'],
    ];

    public function run(): void
    {
        $courses = Course::query()->get();

        if ($courses->isEmpty()) {
            $this->command?->error('Course kosong. Jalankan CourseSeeder dulu.');

            return;
        }

        $this->ensureDemoLiterature();

        DB::transaction(function () use ($courses) {
            foreach ($courses as $course) {
                $order = 1;
                foreach ($this->activities as $act) {
                    if ($course->access_mode === 'scheduled' && $act['type'] === 'meeting') {
                        continue;
                    }

                    // Reuse IDs so existing learning records and variant assignments remain valid.
                    $activity = Activity::firstOrCreate([
                        'course_id' => $course->id,
                        'type' => $act['type'],
                        'title' => $act['title'],
                    ], [
                        'timer' => in_array($act['type'], ['pre-test', 'post-test'], true) ? 600 : 0,
                        'value' => $this->weight($act['type']),
                        'instruction' => $this->instruction($act['type'], $course),
                        'sort_order' => $order,
                    ]);
                    $this->fillMissing($activity, ['instruction' => $this->instruction($act['type'], $course)]);
                    $updates = [];
                    if (! $activity->sort_order) $updates['sort_order'] = $order;
                    if (! $activity->timer && in_array($act['type'], ['pre-test', 'post-test'], true)) $updates['timer'] = 600;
                    if (! $activity->value) $updates['value'] = $this->weight($act['type']);
                    if ($updates) $activity->update($updates);
                    $order++;

                    if (in_array($activity->type, ['pre-test', 'post-test'], true)) {
                        $question = Question::firstOrCreate(['activity_id' => $activity->id], [
                            'description' => $activity->title.' untuk pelatihan '.$course->title,
                            'type' => $activity->type === 'pre-test' ? 'pre' : 'post',
                            'min_score' => 70,
                            'is_loop' => 1,
                        ]);
                        $this->fillMissing($question, ['description' => $activity->title, 'type' => $activity->type === 'pre-test' ? 'pre' : 'post']);
                        $this->seedQuestions($question, $course);
                    } elseif (in_array($activity->type, ['video', 'meeting'], true)) {
                        $defaults = [
                            'type' => $activity->type,
                            'description' => $activity->type === 'video' ? 'Video pengantar pembelajaran '.$course->title : 'Diskusi daring dan tanya jawab materi '.$course->title,
                            'url' => $activity->type === 'video' ? 'https://www.youtube.com/embed/aqz-KE-bpKQ' : 'https://meet.jit.si/gapura-demo',
                            'duration' => $activity->type === 'video' ? 60 : 300,
                            'end_meeting' => $activity->type === 'meeting' ? 1 : 0,
                        ];
                        $detail = Interactive::withTrashed()->firstOrCreate(['activity_id' => $activity->id], $defaults);
                        if ($detail->trashed()) $detail->restore();
                        $this->fillMissing($detail, $defaults);
                    } elseif ($activity->type === 'literature') {
                        $defaults = ['sub_title' => 'Panduan Pembelajaran GAPURA', 'content' => 'gapura-demo-guide.pdf', 'duration' => 60];
                        $detail = Literature::withTrashed()->firstOrCreate(['activity_id' => $activity->id], $defaults);
                        if ($detail->trashed()) $detail->restore();
                        $this->fillMissing($detail, $defaults);
                    } elseif ($activity->type === 'task') {
                        $defaults = ['description' => 'Susun rencana penerapan materi '.$course->title.' di unit kerja Anda. Jelaskan masalah, langkah perbaikan, dan indikator keberhasilan. Unggah jawaban dalam PDF, maksimum 2 MB.', 'is_calculated' => false];
                        $detail = Task::withTrashed()->firstOrCreate(['activity_id' => $activity->id], $defaults);
                        if ($detail->trashed()) $detail->restore();
                        $this->fillMissing($detail, $defaults);
                    }
                }
            }
        });

        $this->command?->info($courses->count().' kelas demo dilengkapi dengan kegiatan dan materi pembelajaran.');
    }

    private function fillMissing(Model $model, array $defaults): void
    {
        $missing = array_filter($defaults, fn ($value, $key) => blank($model->getAttribute($key)), ARRAY_FILTER_USE_BOTH);
        if ($missing) $model->update($missing);
    }

    private function weight(string $type): int
    {
        return ['pre-test' => 10, 'video' => 20, 'literature' => 10, 'task' => 20, 'post-test' => 40, 'meeting' => 0][$type];
    }

    private function instruction(string $type, Course $course): string
    {
        $steps = [
            'pre-test' => 'Kerjakan lima soal untuk mengukur pemahaman awal. Pilih satu jawaban untuk setiap soal, kemudian klik Kirim Jawaban. Waktu pengerjaan 10 menit.',
            'post-test' => 'Kerjakan lima soal evaluasi setelah mempelajari materi. Pilih satu jawaban untuk setiap soal, kemudian klik Kirim Jawaban. Waktu pengerjaan 10 menit.',
            'video' => 'Tonton video contoh hingga selesai. Tombol kirim tersedia setelah durasi belajar berakhir.',
            'literature' => 'Baca PDF panduan pembelajaran dan catat hal yang dapat diterapkan di unit kerja Anda. Tombol kirim tersedia setelah durasi belajar berakhir.',
            'task' => 'Susun rencana penerapan materi dalam dokumen PDF. Unggah file berukuran maksimum 2 MB, kemudian klik Kirim Jawaban.',
            'meeting' => 'Buka tautan ruang diskusi demo untuk mengikuti pertemuan daring. Setelah kegiatan selesai, tandai pertemuan selesai.',
        ];
        return '<h2>Petunjuk Kegiatan</h2><p>Pelatihan: <strong>'.e($course->title).'</strong>.</p><p>'.$steps[$type].'</p><p>Setelah selesai, lanjutkan kegiatan sesuai urutan agenda.</p>';
    }

    private function ensureDemoLiterature(): void
    {
        $path = public_path(Literature::$directoryDocument.'gapura-demo-guide.pdf');
        if (File::exists($path)) return;
        File::ensureDirectoryExists(dirname($path));
        Pdf::loadHTML('<html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans;font-size:12px;line-height:1.7;color:#334155}h1{color:#075d5e}h2{font-size:16px}</style></head><body><h1>Panduan Pembelajaran GAPURA</h1><p>Materi contoh untuk mencoba alur pembelajaran aparatur.</p><h2>1. Persiapan</h2><p>Baca instruksi dan kerjakan pre-test untuk mengukur pemahaman awal.</p><h2>2. Pembelajaran</h2><p>Pelajari video dan bahan bacaan. Identifikasi kebutuhan unit kerja, jaga kerahasiaan data, dan gunakan informasi sesuai kewenangan.</p><h2>3. Penerapan</h2><p>Susun rencana perbaikan pelayanan yang memuat masalah, langkah kerja, dan indikator keberhasilan. Unggah tugas dalam format PDF.</p><h2>4. Evaluasi</h2><p>Selesaikan post-test dan evaluasi hasil penerapan materi melalui peningkatan kinerja serta umpan balik masyarakat.</p></body></html>')->setPaper('a4')->save($path);
    }

    private function seedQuestions(Question $question, Course $course): void
    {
        $questions = [
            ["Apa tujuan utama pelatihan {$course->title}?", ['Menghindari tanggung jawab pekerjaan', 'Meningkatkan kompetensi aparatur', 'Mengurangi kualitas layanan', 'Mengabaikan kebutuhan masyarakat']],
            ['Apa yang perlu dilakukan sebelum menerapkan materi pelatihan?', ['Langsung bertindak tanpa persiapan', 'Memahami konsep dan kebutuhan unit kerja', 'Mengabaikan pedoman kerja', 'Menyalin semua cara tanpa evaluasi']],
            ['Bagaimana aparatur sebaiknya menangani informasi masyarakat?', ['Membagikan semua data kepada publik', 'Menjaga kerahasiaan dan menggunakan data sesuai kewenangan', 'Mengubah data tanpa persetujuan', 'Menyimpan data di akun publik']],
            ['Apa tindakan yang tepat ketika menemukan kendala dalam pelayanan?', ['Membiarkan kendala tanpa tindak lanjut', 'Mengidentifikasi masalah dan berkoordinasi mencari solusi', 'Menyalahkan masyarakat', 'Menghentikan seluruh layanan tanpa alasan']],
            ['Bagaimana keberhasilan penerapan hasil pelatihan dievaluasi?', ['Hanya menghitung jumlah rapat', 'Mengukur perbaikan kinerja dan kualitas layanan', 'Mengabaikan umpan balik', 'Menilai berdasarkan dugaan saja']],
        ];

        for ($number = 1; $number <= 2; $number++) {
            $variant = QuestionVariant::firstOrCreate([
                'question_id' => $question->id,
                'variant' => $number,
            ], [
                'code' => strtoupper($question->type).'-'.$number,
                'variant' => $number,
                'mix' => false,
            ]);

            foreach ($questions as $index => [$description, $options]) {
                $item = QuestionItem::firstOrCreate([
                    'question_variant_id' => $variant->id,
                    'description' => $description,
                ], [
                    'score' => 20,
                    'sort_order' => $index + 1,
                ]);

                foreach ($options as $index => $option) {
                    $isCorrect = $index === 1;
                    $optionText = $isCorrect ? $option.' (Benar)' : $option;
                    $questionOption = QuestionOption::firstOrCreate([
                        'question_item_id' => $item->id,
                        'sort_order' => $index + 1,
                    ], [
                        'option' => $optionText,
                        'is_correct' => $isCorrect,
                    ]);
                    // Mark existing demo answers without overwriting edited options.
                    if ($isCorrect && $questionOption->is_correct && $questionOption->option === $option) {
                        $questionOption->update(['option' => $optionText]);
                    }
                }
            }
        }
    }

}
