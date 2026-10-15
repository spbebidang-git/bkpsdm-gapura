<?php

namespace Tests\Feature;

use App\Models\Course\Activity\Activity;
use App\Models\Course\Course;
use App\Models\Course\Schedule;
use App\Models\Identity\User;
use App\Services\Learning\ParticipantEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ParticipantCourseEntryTest extends TestCase
{
    use RefreshDatabase;

    private function participant(): User
    {
        Role::findOrCreate('participant', 'web');
        $user = User::factory()->create();
        $user->assignRole('participant');
        return $user;
    }

    public function test_detail_links_to_the_participants_schedule_and_first_ordered_activity(): void
    {
        $user = $this->participant();
        $course = Course::factory()->scheduled()->create();
        Schedule::create(['course_id' => $course->id]);
        $joined = Schedule::create(['course_id' => $course->id]);
        $joined->participants()->attach($user->id);
        app(ParticipantEnrollmentService::class)->activate($course, $joined, (string) $user->id);
        Activity::create(['course_id' => $course->id, 'type' => 'video', 'title' => 'Materi', 'sort_order' => 2]);
        $first = Activity::create(['course_id' => $course->id, 'type' => 'video', 'title' => 'Pengantar', 'sort_order' => 1]);

        $this->actingAs($user)->get(route('participant.course.show', $course->slug))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Participant/Course/Show')
                ->where('learn.entry_url', route('participant.learn.instruction.index', [$course->slug, $joined->id, $first->id]))
                ->where('learn.entry_message', null)
                ->where('course.schedule.date', null)
                ->has('course.trainers', 0)
                ->has('course.activities', 2)
                ->where('course.activities.0.title', 'Pengantar')
                ->where('course.activities.1.title', 'Materi')
                ->where('course.rating.total', 0)
                ->has('course.reviews', 0));
    }

    public function test_detail_explains_when_material_is_missing(): void
    {
        $user = $this->participant();
        $course = Course::factory()->open()->create();
        $schedule = Schedule::create(['course_id' => $course->id]);
        $schedule->participants()->attach($user->id);
        app(ParticipantEnrollmentService::class)->activate($course, $schedule, (string) $user->id);

        $this->actingAs($user)->get(route('participant.course.show', $course->slug))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('learn.entry_url', null)
                ->where('learn.entry_message', 'Materi pelatihan belum tersedia.'));
    }

    public function test_expired_access_does_not_offer_a_learning_link(): void
    {
        $user = $this->participant();
        $course = Course::factory()->open()->create();
        $schedule = Schedule::create(['course_id' => $course->id]);
        $schedule->participants()->attach($user->id);
        $enrollment = app(ParticipantEnrollmentService::class)->activate($course, $schedule, (string) $user->id);
        $enrollment->update(['access_starts_at' => now()->subDays(5), 'access_ends_at' => now()->subDay()]);
        Activity::create(['course_id' => $course->id, 'type' => 'video', 'title' => 'Pengantar', 'sort_order' => 1]);

        $this->actingAs($user)->get(route('participant.course.show', $course->slug))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('learn.entry_url', null)
                ->where('learn.entry_message', 'Masa akses pelatihan telah berakhir.'));
    }
}
