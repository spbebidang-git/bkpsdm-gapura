<?php

namespace Tests\Feature;

use App\Models\Course\Course;
use App\Models\Identity\User;
use App\Models\Learning\Learn;
use App\Services\Course\CourseService;
use App\Services\Learning\ParticipantEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CourseStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_rating_submission_updates_statistics_on_detail_catalog_and_home_for_both_access_modes(): void
    {
        Role::findOrCreate('participant', 'web');
        foreach (['open', 'scheduled'] as $mode) {
            $course = Course::factory()->create([
                'visibility' => 'public', 'access_mode' => $mode,
                'default_access_hours' => $mode === 'open' ? 72 : null,
            ]);
            $schedule = $course->schedules()->create($mode === 'open' ? [] : [
                'date' => today()->toDateString(), 'time_from' => '00:00:00', 'time_to' => '23:59:59',
            ]);
            foreach ([4, 5] as $index => $stars) {
                $participant = User::factory()->create();
                $participant->assignRole('participant');
                $schedule->participants()->attach($participant->id);
                $enrollment = app(ParticipantEnrollmentService::class)->activate($course, $schedule, $participant->id);
                $learn = Learn::create([
                    'course_id' => $course->id, 'participant_id' => $participant->id,
                    'enrollment_id' => $enrollment->id,
                ]);
                $this->assertStatistics($course, $index + 1, $index, $index === 0 ? 0 : 4);

                $this->actingAs($participant)->post(route('participant.rating.store', [$course->slug, $schedule->id]), [
                    'rating' => $stars, 'description' => 'Materi bermanfaat.',
                ])->assertRedirect(route('participant.survey.index', [$course->slug, $schedule->id]));

                $this->assertNotNull($learn->fresh()->finished_at);
                $this->assertSame('completed', $enrollment->fresh()->status);
                $this->assertStatistics($course, $index + 1, $index + 1, $index === 0 ? 4 : 4.5);
            }
        }
    }

    private function assertStatistics(Course $course, int $participants, int $passed, float $stars): void
    {
        $fresh = $course->fresh();
        $this->assertSame($participants, $fresh->total_participant);
        $this->assertSame($passed, $fresh->total_passed);
        $this->assertEquals($stars, $fresh->getRating()['star']);

        $service = app(CourseService::class);
        foreach ([$service->getAllCourse(), $service->getFeaturedCourses()] as $courses) {
            $item = $courses->firstWhere('id', $course->id);
            $this->assertNotNull($item);
            $this->assertSame($participants, $item['total_participant']);
            $this->assertSame($passed, $item['total_passed']);
            $this->assertEquals($stars, $item['star']);
        }

        $this->get(route('course.show', $course->slug))->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('course.total_participant', $participants)
                ->where('course.total_passed', $passed)
                ->where('rating.star', fn ($value) => (float) $value === $stars)
                ->where('rating.total', $passed));
    }

    public function test_effectiveness_handles_legacy_completion_without_an_enrollment(): void
    {
        $course = Course::factory()->open()->create();
        Learn::create(['course_id' => $course->id, 'participant_id' => User::factory()->create()->id, 'finished_at' => now()]);

        $this->assertSame(0, $course->total_participant);
        $this->assertEquals(0, $course->efektifitas);
    }
}
