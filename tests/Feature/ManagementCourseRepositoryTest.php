<?php

namespace Tests\Feature;

use App\Models\Course\Course;
use App\Models\Course\Schedule;
use App\Models\Course\Trainer;
use App\Models\Identity\User;
use App\Repositories\Course\Contracts\CourseRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ManagementCourseRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_management_queries_and_trainer_access_are_encapsulated_in_course_repository(): void
    {
        $course = Course::factory()->open()->create();
        $schedule = Schedule::create(['course_id' => $course->id]);
        $user = User::factory()->create();
        $trainer = Trainer::create(['user_id' => $user->id, 'name' => 'Trainer GAPURA']);
        $course->trainers()->attach($trainer);

        $courses = $this->app->make(CourseRepositoryInterface::class);
        $overview = $courses->findForManagementOverview($course->id);
        $detail = $courses->findForManagementDetail($course->id);

        $this->assertTrue($overview->relationLoaded('category'));
        $this->assertTrue($overview->relationLoaded('schedules'));
        $this->assertTrue($overview->schedules->contains($schedule));
        $this->assertTrue($detail->relationLoaded('employeeStatus'));
        $this->assertTrue($courses->isManagedByTrainer($course, $user->id));
    }

    public function test_course_index_lists_recently_modified_courses_first(): void
    {
        $older = Course::factory()->open()->create();
        $newer = Course::factory()->open()->create();
        $recentlyEdited = Course::factory()->open()->create();

        DB::table('courses')->where('id', $older->id)->update(['updated_at' => '2026-01-01 10:00:00']);
        DB::table('courses')->where('id', $newer->id)->update(['updated_at' => '2026-01-03 10:00:00']);
        DB::table('courses')->where('id', $recentlyEdited->id)->update(['updated_at' => '2026-01-04 10:00:00']);

        $ids = $this->app->make(CourseRepositoryInterface::class)->getForInstructor()->pluck('id')->all();

        $this->assertSame([$recentlyEdited->id, $newer->id, $older->id], $ids);
    }
}
