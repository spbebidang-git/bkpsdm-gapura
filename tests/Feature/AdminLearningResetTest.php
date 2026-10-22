<?php

namespace Tests\Feature;

use App\Models\Course\Activity\Activity;
use App\Models\Course\Course;
use App\Models\Course\Schedule;
use App\Models\Identity\User;
use App\Models\Learning\Enrollment;
use App\Models\Learning\Learn;
use App\Models\Learning\LearnItem;
use App\Services\Learning\ParticipantEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminLearningResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_super_admin_can_reset_one_participants_learning_for_one_course(): void
    {
        foreach (['super_admin', 'administrator', 'participant'] as $role) {
            Role::findOrCreate($role, 'web');
        }
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');
        $administrator = User::factory()->create();
        $administrator->assignRole('administrator');
        $participant = User::factory()->create();
        $participant->assignRole('participant');
        $otherParticipant = User::factory()->create();
        $otherParticipant->assignRole('participant');
        $course = Course::factory()->open()->create();
        $schedule = Schedule::create(['course_id' => $course->id]);
        $schedule->participants()->attach([$participant->id, $otherParticipant->id]);
        $service = app(ParticipantEnrollmentService::class);
        $enrollment = $service->activate($course, $schedule, (string) $participant->id);
        $otherEnrollment = $service->activate($course, $schedule, (string) $otherParticipant->id);
        $learn = Learn::create(['participant_id' => $participant->id, 'course_id' => $course->id,
            'enrollment_id' => $enrollment->id, 'finished_at' => now()]);
        $otherLearn = Learn::create(['participant_id' => $otherParticipant->id, 'course_id' => $course->id,
            'enrollment_id' => $otherEnrollment->id]);
        $activity = Activity::create(['course_id' => $course->id, 'type' => 'video', 'title' => 'Materi']);
        $item = LearnItem::create(['learn_id' => $learn->id, 'activity_id' => $activity->id,
            'title' => 'Materi', 'type' => 'video', 'status' => 'done']);
        $url = route('admin.course.participant.reset-learning', [$course->slug, $participant->id]);

        $this->actingAs($administrator)->post($url)->assertForbidden();
        $this->assertFalse($learn->fresh()->trashed());

        $this->actingAs($superAdmin)->post($url)
            ->assertRedirect(route('admin.course.overview', $course->slug));
        $this->assertTrue(Learn::withTrashed()->findOrFail($learn->id)->trashed());
        $this->assertTrue(LearnItem::withTrashed()->findOrFail($item->id)->trashed());
        $this->assertTrue(Enrollment::withTrashed()->findOrFail($enrollment->id)->trashed());
        $this->assertFalse($otherLearn->fresh()->trashed());
        $this->assertFalse($otherEnrollment->fresh()->trashed());

        $newEnrollment = $service->activate($course, $schedule, (string) $participant->id);
        $this->assertNotSame($enrollment->id, $newEnrollment->id);
        $this->assertNull($newEnrollment->learn()->first());
    }
}
