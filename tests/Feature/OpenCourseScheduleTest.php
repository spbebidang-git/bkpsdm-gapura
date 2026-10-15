<?php

namespace Tests\Feature;

use App\Models\Course\Course;
use App\Models\Course\Schedule;
use App\Services\Course\CourseService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CourseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OpenCourseScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_open_course_has_an_activation_schedule_and_keeps_it_on_reseed(): void
    {
        $this->seed([CategorySeeder::class, CourseSeeder::class]);
        $course = Course::where('slug', 'literasi-digital-aparatur')->firstOrFail();
        $schedule = $course->schedules()->sole();

        $this->assertNull($schedule->getRawOriginal('date'));
        $this->assertNull($schedule->getRawOriginal('time_from'));
        $this->assertNull($schedule->getRawOriginal('time_to'));
        $this->assertNull($schedule->getRawOriginal('quota'));

        $this->get(route('course.show', $course->slug))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Course/Show')
                ->where('course.type', 'unschedule')
                ->has('schedules', 1)
                ->where('schedules.0.id', $schedule->id)
                ->where('schedules.0.can_enter', true));

        $this->seed(CourseSeeder::class);
        $this->assertSame($schedule->id, $course->schedules()->sole()->id);
    }

    public function test_backfill_repairs_missing_open_schedules_without_duplicating_them(): void
    {
        $open = Course::factory()->open()->create();
        $scheduled = Course::factory()->scheduled()->create();
        $repair = require database_path('migrations/2026_09_25_000004_create_missing_open_course_schedules.php');

        $repair->up();
        $repair->up();

        $this->assertSame(1, $open->schedules()->count());
        $this->assertSame(0, $scheduled->schedules()->count());
    }

    public function test_updating_an_open_course_without_a_schedule_creates_an_empty_schedule(): void
    {
        $course = Course::factory()->open()->create();

        $this->app->make(CourseService::class)->updateCourse($course, [
            'type' => 'unschedule',
            'category' => $course->category_id,
            'employee_status' => [],
            'title' => $course->title,
            'short_description' => $course->short_description,
            'description' => $course->description,
            'teaser_video' => $course->teaser_video,
            'expired' => 72,
            'jam_pelajaran' => $course->jam_pelajaran,
            'status' => $course->status,
            'trainers' => [],
        ]);

        $this->assertDatabaseCount('schedules', 1);
        $this->assertDatabaseHas('schedules', [
            'course_id' => $course->id,
            'date' => null,
            'time_from' => null,
            'time_to' => null,
            'quota' => null,
        ]);
    }

    public function test_updating_course_details_does_not_replace_existing_schedules(): void
    {
        $course = Course::factory()->scheduled()->create();
        $schedule = Schedule::create([
            'course_id' => $course->id,
            'date' => '2026-10-20',
            'time_from' => '09:00',
            'time_to' => '11:00',
            'quota' => 30,
        ]);

        $this->app->make(CourseService::class)->updateCourse($course, [
            'type' => 'schedule',
            'category' => $course->category_id,
            'employee_status' => [],
            'title' => 'Updated course title',
            'short_description' => $course->short_description,
            'description' => $course->description,
            'teaser_video' => $course->teaser_video,
            'jam_pelajaran' => $course->jam_pelajaran,
            'status' => 'draft',
            'trainers' => [],
        ]);

        $this->assertDatabaseHas('schedules', [
            'id' => $schedule->id,
            'course_id' => $course->id,
            'date' => '2026-10-20',
            'quota' => 30,
            'deleted_at' => null,
        ]);
    }
}
