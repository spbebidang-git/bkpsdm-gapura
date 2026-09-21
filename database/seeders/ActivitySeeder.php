<?php

namespace Database\Seeders;

use App\Models\Course\Activity\Activity;
use App\Models\Course\Course;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActivitySeeder extends Seeder
{
    /** Nama acak untuk demo — dipilih 5 tanpa ulang. */
    private array $names = [
        'Diklat Pelayanan Publik',
        'Diklat Manajemen Kinerja',
        'Diklat Etika ASN',
        'Diklat Komunikasi Efektif',
        'Diklat Kepemimpinan',
        'Diklat Pengelolaan Arsip',
        'Diklat Analisis Data',
        'Diklat Public Speaking',
    ];

    /** Kegiatan lengkap; meeting dilewati utk kelas scheduled. */
    private array $activities = [
        ['type' => 'pre-test', 'title' => 'Pre-Test'],
        ['type' => 'video', 'title' => 'Video Presentasi'],
        ['type' => 'literature', 'title' => 'Literature'],
        ['type' => 'task', 'title' => 'Tugas'],
        ['type' => 'post-test', 'title' => 'Post-Test'],
        ['type' => 'meeting', 'title' => 'Pertemuan Daring'],
    ];

    public function run(): void
    {
        $categoryIds = DB::table('categories')->pluck('id');

        if ($categoryIds->isEmpty()) {
            $this->command?->error('Category kosong. Jalankan CategorySeeder dulu.');

            return;
        }

        $picked = collect($this->names)->shuffle()->take(5)->values();

        for ($i = 0; $i < 5; $i++) {
            $mode = $i < 3 ? 'open' : 'scheduled'; // 3 unschedule, 2 schedule
            $slug = 'kelas-' . ($i + 1);

            $course = Course::updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id' => $categoryIds->random(),
                    'access_mode' => $mode,
                    'default_access_hours' => $mode === 'open' ? 72 : null,
                    'title' => $picked[$i],
                    'short_description' => 'Kelas demo ' . $picked[$i],
                    'description' => '<p>Kelas demo: ' . e($picked[$i]) . '.</p>',
                    'thumbnail' => 'default-class.jpg',
                    'status' => 'publish',
                    'is_locked' => false,
                ]
            );

            // reset kegiatan kelas ini, lalu isi lengkap (idempotent)
            $course->activities()->forceDelete();

            foreach ($this->activities as $act) {
                if ($mode === 'scheduled' && $act['type'] === 'meeting') {
                    continue; // scheduled tidak ada meeting
                }

                Activity::create([
                    'course_id' => $course->id,
                    'type' => $act['type'],
                    'title' => $act['title'],
                    'timer' => 0,
                    'value' => 0,
                    'instruction' => null,
                    'sort_order' => 0,
                ]);
            }
        }

        $this->command?->info('5 kelas (nama acak) dengan kegiatan lengkap berhasil dibuat.');
    }
}
