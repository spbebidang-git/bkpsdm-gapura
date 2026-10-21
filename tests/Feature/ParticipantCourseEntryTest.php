<?php

namespace Tests\Feature;

use App\Models\Course\Activity\Activity;
use App\Models\Course\Activity\Interactive;
use App\Models\Course\Course;
use App\Models\Course\Schedule;
use App\Models\Identity\User;
use App\Models\Learning\Learn;
use App\Models\Learning\LearnItem;
use App\Models\Learning\Enrollment;
use App\Services\Learning\ParticipantEnrollmentService;
use Carbon\Carbon;
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

    public function test_first_activity_opens_without_redirecting_back_to_itself(): void
    {
        $user = $this->participant();
        $course = Course::factory()->open()->create();
        $schedule = Schedule::create(['course_id' => $course->id]);
        $schedule->participants()->attach($user->id);
        app(ParticipantEnrollmentService::class)->activate($course, $schedule, (string) $user->id);
        $first = Activity::create(['course_id' => $course->id, 'type' => 'video', 'title' => 'Pengantar', 'sort_order' => 1]);
        Interactive::create(['activity_id' => $first->id, 'url' => 'https://example.test/video', 'duration' => 60]);

        $this->actingAs($user)->get(route('participant.learn.instruction.index', [$course->slug, $schedule->id, $first->id]))
            ->assertOk()->assertInertia(fn (Assert $page) => $page->component('Learn/Instruction/Index'));
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

    public function test_detail_resumes_the_first_unfinished_activity(): void
    {
        $user = $this->participant();
        $course = Course::factory()->open()->create();
        $schedule = Schedule::create(['course_id' => $course->id]);
        $schedule->participants()->attach($user->id);
        $enrollment = app(ParticipantEnrollmentService::class)->activate($course, $schedule, (string) $user->id);
        $first = Activity::create(['course_id' => $course->id, 'type' => 'video', 'title' => 'Awal', 'sort_order' => 1]);
        $next = Activity::create(['course_id' => $course->id, 'type' => 'task', 'title' => 'Tugas', 'sort_order' => 2]);
        $learn = Learn::create(['participant_id' => $user->id, 'course_id' => $course->id, 'enrollment_id' => $enrollment->id]);
        LearnItem::create(['learn_id' => $learn->id, 'activity_id' => $first->id, 'title' => $first->title, 'type' => 'video', 'status' => 'done', 'locked_at' => now()]);

        $this->actingAs($user)->get(route('participant.course.show', $course->slug))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('learn.entry_url', route('participant.learn.instruction.index', [$course->slug, $schedule->id, $next->id])));
    }

    public function test_reset_soft_deletes_progress_and_enrollment_for_reactivation(): void
    {
        $user = $this->participant();
        $course = Course::factory()->open()->create();
        $schedule = Schedule::create(['course_id' => $course->id]);
        $schedule->participants()->attach($user->id);
        $enrollment = app(ParticipantEnrollmentService::class)->activate($course, $schedule, (string) $user->id);
        $activity = Activity::create(['course_id' => $course->id, 'type' => 'pre-test', 'title' => 'Tes', 'sort_order' => 1]);
        $learn = Learn::create(['participant_id' => $user->id, 'course_id' => $course->id, 'enrollment_id' => $enrollment->id, 'finished_at' => now(), 'url_certificate' => 'https://example.test/cert.pdf', 'is_post_certificate' => true]);
        $enrollment->update(['status' => 'completed']);
        LearnItem::create(['learn_id' => $learn->id, 'activity_id' => $activity->id, 'title' => $activity->title, 'type' => 'pre-test', 'status' => 'done', 'response' => serialize([['score' => 80]])]);

        $this->actingAs($user)->post(route('participant.course.reset', $course->slug))->assertRedirect(route('participant.course.show', $course->slug));
        $this->assertTrue(Learn::withTrashed()->findOrFail($learn->id)->trashed());
        $this->assertTrue($learn->learnItems()->withTrashed()->first()->trashed());
        $this->assertTrue(Enrollment::withTrashed()->findOrFail($enrollment->id)->trashed());

        $this->actingAs($user)->get(route('participant.course.show', $course->slug))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('learn.needs_activation', true)
                ->where('learn.entry_url', null)
                ->where('learn.finished_at', null)
                ->where('learn.url_certificate', null));

        $newEnrollment = app(ParticipantEnrollmentService::class)->activate($course, $schedule, (string) $user->id);
        $this->assertNotSame($enrollment->id, $newEnrollment->id);
        $this->assertSame('active', $newEnrollment->status);
        $this->assertNull($newEnrollment->learn()->first());
    }

    public function test_reset_is_available_after_access_expired(): void
    {
        $user = $this->participant();
        $course = Course::factory()->open()->create();
        $schedule = Schedule::create(['course_id' => $course->id]);
        $schedule->participants()->attach($user->id);
        $enrollment = app(ParticipantEnrollmentService::class)->activate($course, $schedule, (string) $user->id);
        $learn = Learn::create(['participant_id' => $user->id, 'course_id' => $course->id, 'enrollment_id' => $enrollment->id]);
        $activity = Activity::create(['course_id' => $course->id, 'type' => 'video', 'title' => 'Materi', 'sort_order' => 1]);
        LearnItem::create(['learn_id' => $learn->id, 'activity_id' => $activity->id, 'title' => 'Materi', 'type' => 'video', 'status' => 'in_progress']);
        $enrollment->update(['status' => 'expired', 'access_ends_at' => now()->subMinute()]);

        $this->actingAs($user)->post(route('participant.course.reset', $course->slug))->assertRedirect(route('participant.course.show', $course->slug));
        $this->assertNotNull(Enrollment::withTrashed()->find($enrollment->id)->deleted_at);
        $this->assertNotNull(Learn::withTrashed()->find($learn->id)->deleted_at);
    }

    public function test_reactivation_of_an_expired_scheduled_class_restarts_its_original_access_duration(): void
    {
        Carbon::setTestNow('2026-10-22 08:00:00');
        try {
            $user = $this->participant();
            $course = Course::factory()->scheduled()->create();
            $schedule = Schedule::create([
                'course_id' => $course->id,
                'date' => '2026-10-20',
                'time_from' => '09:00:00',
                'time_to' => '11:00:00',
                'access_from' => '2026-10-20 09:00:00',
                'access_to' => '2026-10-20 11:00:00',
            ]);
            $schedule->participants()->attach($user->id);
            $old = Enrollment::query()->create([
                'participant_id' => $user->id,
                'course_id' => $course->id,
                'schedule_id' => $schedule->id,
                'status' => 'expired',
                'enrolled_at' => '2026-10-20 08:00:00',
                'access_starts_at' => '2026-10-20 09:00:00',
                'access_ends_at' => '2026-10-20 11:00:00',
            ]);
            $old->delete();

            $new = app(ParticipantEnrollmentService::class)->activate($course, $schedule, (string) $user->id);

            $this->assertTrue($new->access_starts_at->equalTo(now()));
            $this->assertTrue($new->access_ends_at->equalTo(now()->addHours(2)));
            $this->assertSame('active', $new->status);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_participant_cannot_reset_another_participants_course(): void
    {
        $owner = $this->participant();
        $visitor = $this->participant();
        $course = Course::factory()->open()->create();
        $schedule = Schedule::create(['course_id' => $course->id]);
        $schedule->participants()->attach($owner->id);
        app(ParticipantEnrollmentService::class)->activate($course, $schedule, (string) $owner->id);

        $this->actingAs($visitor)->post(route('participant.course.reset', $course->slug))->assertNotFound();
    }
}
