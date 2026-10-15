<?php

namespace Tests\Feature;

use App\Http\Middleware\IsAdminMiddleware;
use App\Models\Course\Course;
use App\Models\Course\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseScheduleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(IsAdminMiddleware::class);
    }

    public function test_schedule_can_be_added_and_edited_without_sending_course_details(): void
    {
        $course = Course::factory()->scheduled()->draft()->create();
        $payload = ['date' => '2026-10-20', 'time_from' => '09:00', 'time_to' => '11:00', 'quota' => 30];

        $this->post(route('admin.course.schedules.store', $course->slug), $payload)
            ->assertRedirect(route('admin.course.schedules.index', $course->slug));
        $schedule = Schedule::query()->where('course_id', $course->id)->firstOrFail();

        $this->put(route('admin.course.schedules.update', [$course->slug, $schedule->id]), [...$payload, 'quota' => 40])
            ->assertRedirect(route('admin.course.schedules.index', $course->slug));

        $this->assertDatabaseHas('schedules', ['id' => $schedule->id, 'quota' => 40]);
        $this->assertSame('draft', $course->fresh()->status);
    }

    public function test_invalid_time_order_is_rejected(): void
    {
        $course = Course::factory()->scheduled()->draft()->create();

        $this->from(route('admin.course.schedules.index', $course->slug))
            ->post(route('admin.course.schedules.store', $course->slug), [
                'date' => '2026-10-20', 'time_from' => '11:00', 'time_to' => '09:00', 'quota' => 30,
            ])
            ->assertSessionHasErrors('time_to');

        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_published_course_cannot_lose_its_last_schedule(): void
    {
        $course = Course::factory()->scheduled()->published()->create();
        $schedule = Schedule::create(['course_id' => $course->id, 'date' => '2026-10-20', 'time_from' => '09:00', 'time_to' => '11:00', 'quota' => 30]);

        $this->from(route('admin.course.schedules.index', $course->slug))
            ->delete(route('admin.course.schedules.destroy', [$course->slug, $schedule->id]))
            ->assertSessionHasErrors('schedule');

        $this->assertDatabaseHas('schedules', ['id' => $schedule->id, 'deleted_at' => null]);
    }
}
