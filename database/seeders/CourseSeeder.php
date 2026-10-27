<?php

namespace Database\Seeders;

use App\Models\Course\Category;
use App\Models\Course\Course;
use App\Models\Course\Schedule;
use App\Models\Course\Trainer;
use App\Models\Identity\EmployeeStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CourseSeeder extends Seeder
{
    /** Dua kelas inti untuk development: satu scheduled, satu open. */
    private array $courses = [
        ['title' => 'Pelayanan Publik Terjadwal', 'jp' => 12, 'mode' => 'scheduled'],
        ['title' => 'Literasi Digital Aparatur', 'jp' => 8, 'mode' => 'open'],
    ];

    public function run(): void
    {
        $categories = Category::query()
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->orderBy('code')
            ->get();

        if ($categories->isEmpty()) {
            $this->command?->error('Category kosong. Jalankan CategorySeeder dulu.');

            return;
        }

        $statuses = EmployeeStatus::all();
        if ($statuses->isEmpty()) {
            foreach (['CPNS', 'PNS', 'PPPK', 'THL'] as $title) {
                EmployeeStatus::firstOrCreate(['title' => $title]);
            }
            $statuses = EmployeeStatus::all();
        }

        $created = 0;
        $demoTrainer = Trainer::whereHas('user', fn ($query) => $query->where('email', 'trainer@example.test'))->first();

        foreach ($this->courses as $i => $row) {
            $slug = Str::slug($row['title']);
            $mode = $row['mode']; // open | scheduled
            $category = $categories[$i % $categories->count()];

            $course = Course::withTrashed()->updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id' => $category->id,
                    'access_mode' => $mode,
                    'default_access_hours' => $mode === 'open' ? 72 : null,
                    'title' => $row['title'],
                    'short_description' => "Pelatihan {$row['title']} untuk meningkatkan kompetensi ASN. Durasi {$row['jp']} JP.",
                    'description' => '<p>Materi mencakup konsep, praktik, dan studi kasus terkait <strong>'
                        .e($row['title'])
                        .'</strong>. Pengguna diharapkan mampu menerapkan ilmu di unit kerja masing-masing.</p>'
                        .'<ul><li>Pemahaman konsep</li><li>Praktik terapan</li><li>Evaluasi pre/post test</li></ul>',
                    'thumbnail' => '',
                    'jam_pelajaran' => $row['jp'],
                    'teaser_video' => null,
                    'outline_topic' => "1. Pendahuluan\n2. Materi inti\n3. Praktik\n4. Evaluasi",
                    'status' => 'publish',
                    'visibility' => 'public',
                    'is_locked' => false,
                    'deleted_at' => null,
                ]
            );

            $course->opds()->detach();
            $course->employeeStatus()->sync(
                $statuses->random(min(4, max(2, $statuses->count())))->pluck('id')->all()
            );
            if ($demoTrainer) {
                $course->trainers()->sync([$demoTrainer->id]);
            }

            if ($mode === 'scheduled') {
                // replace schedules for this course (idempotent re-seed)
                $course->schedules()->forceDelete();
                $this->seedSchedules($course, $i);
            } else {
                // Open courses need an empty schedule for participant activation.
                // Preserve it on subsequent seeds so existing enrolments survive.
                $course->schedules()->firstOrCreate([]);
            }

            $created++;
        }

        // buang sisa seeder lama (slug -1, -2, factory junk)
        $keep = collect($this->courses)->map(fn ($r) => Str::slug($r['title']))->all();
        $trashed = Course::withTrashed()->whereNotIn('slug', $keep)->get();
        foreach ($trashed as $extra) {
            $extra->schedules()->forceDelete();
            $extra->employeeStatus()->detach();
            $extra->forceDelete();
        }

        $this->command?->info("CourseSeeder: {$created} kelas (total DB: ".Course::count().').');

    }

    private function seedSchedules(Course $course, int $index): void
    {
        $start = now()->startOfDay()->addDays(($index % 5) + 1);

        Schedule::create([
            'course_id' => $course->id,
            'title' => 'Sesi 1 — '.$course->title,
            'register_from' => $start->copy()->subDays(7),
            'register_to' => $start->copy()->subDay()->endOfDay(),
            'access_from' => $start->copy()->setTime(8, 0),
            'access_to' => $start->copy()->setTime(16, 0),
            'date' => $start->toDateString(),
            'time_from' => '08:00:00',
            'time_to' => '16:00:00',
            'quota' => 30 + ($index % 3) * 10,
        ]);

        // second session for half of scheduled
        if ($index % 2 === 0) {
            $day2 = $start->copy()->addDays(1);
            Schedule::create([
                'course_id' => $course->id,
                'title' => 'Sesi 2 — '.$course->title,
                'register_from' => $start->copy()->subDays(7),
                'register_to' => $start->copy()->subDay()->endOfDay(),
                'access_from' => $day2->copy()->setTime(8, 0),
                'access_to' => $day2->copy()->setTime(12, 0),
                'date' => $day2->toDateString(),
                'time_from' => '08:00:00',
                'time_to' => '12:00:00',
                'quota' => 30 + ($index % 3) * 10,
            ]);
        }
    }
}
