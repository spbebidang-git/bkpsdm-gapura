<?php

namespace Tests\Feature;

use App\Models\Course\Category;
use App\Models\Course\Course;
use App\Models\Identity\User;
use App\Models\Learning\Enrollment;
use App\Models\Learning\Learn;
use App\Models\Learning\ParticipantNotification;
use Spatie\Permission\Models\Role;
use App\Services\Learning\LearnLifecycleService;
use App\Services\Learning\ParticipantEnrollmentService;
use App\Services\Learning\ParticipantNotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParticipantNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_event_is_idempotent_per_participant(): void
    {
        $participant = User::factory()->create();
        $service = app(ParticipantNotificationService::class);

        $service->create('deadline:enrollment:1', 'deadline', $participant->id, ['title' => 'Tenggat']);
        $service->create('deadline:enrollment:1', 'deadline', $participant->id, ['title' => 'Tenggat']);

        $this->assertSame(1, ParticipantNotification::query()->where('participant_id', $participant->id)->count());
    }

    public function test_completion_and_first_certificate_each_create_one_notice(): void
    {
        $participant = User::factory()->create();
        $course = Course::factory()->open()->create(['category_id' => Category::factory()->create()->id]);
        $learn = Learn::query()->create(['participant_id' => $participant->id, 'course_id' => $course->id]);
        $lifecycle = app(LearnLifecycleService::class);

        $lifecycle->finish($learn);
        $finishedAt = $learn->fresh()->finished_at;
        $lifecycle->finish($learn->fresh());
        $lifecycle->setCertificate($learn->fresh(), 'https://example.test/certificate.pdf');
        $lifecycle->setCertificate($learn->fresh(), 'https://example.test/certificate.pdf');

        $this->assertSame(1, ParticipantNotification::query()->where('participant_id', $participant->id)->where('type', 'completed')->count());
        $this->assertSame(1, ParticipantNotification::query()->where('participant_id', $participant->id)->where('type', 'certificate')->count());
        $this->assertTrue($learn->fresh()->finished_at->equalTo($finishedAt));
    }

    public function test_completed_learning_remains_readable_but_not_writable_after_expiry(): void
    {
        $participant = User::factory()->create();
        $course = Course::factory()->open()->create(['category_id' => Category::factory()->create()->id]);
        $enrollment = Enrollment::query()->create([
            'participant_id' => $participant->id,
            'course_id' => $course->id,
            'status' => 'expired',
            'enrolled_at' => now()->subDays(2),
            'access_starts_at' => now()->subDays(2),
            'access_ends_at' => now()->subDay(),
        ]);
        Learn::query()->create([
            'participant_id' => $participant->id,
            'course_id' => $course->id,
            'enrollment_id' => $enrollment->id,
            'finished_at' => now()->subDays(2),
        ]);

        $service = app(ParticipantEnrollmentService::class);
        $this->assertTrue($service->canRead($enrollment->fresh()));
        $this->assertFalse($service->canWrite($enrollment->fresh()));
    }

    public function test_deadline_command_sends_only_the_nearest_reminder_and_is_idempotent(): void
    {
        Carbon::setTestNow('2026-10-10 12:00:00');
        try {
            foreach ([24 => 'reminder_24h', 1 => 'reminder_1h', -1 => 'expired'] as $hours => $event) {
                $participant = User::factory()->create();
                $course = Course::factory()->open()->create(['category_id' => Category::factory()->create()->id]);
                Enrollment::query()->create([
                    'participant_id' => $participant->id,
                    'course_id' => $course->id,
                    'status' => 'active',
                    'enrolled_at' => now()->subDay(),
                    'access_starts_at' => now()->subDay(),
                    'access_ends_at' => now()->addHours($hours),
                ]);
            }

            $this->artisan('participant:notify-deadlines')->assertSuccessful();
            $this->artisan('participant:notify-deadlines')->assertSuccessful();

            $this->assertSame(1, ParticipantNotification::query()->where('event_key', 'like', '%reminder_24h')->count());
            $this->assertSame(1, ParticipantNotification::query()->where('event_key', 'like', '%reminder_1h')->count());
            $this->assertSame(1, ParticipantNotification::query()->where('event_key', 'like', '%expired')->count());
            $this->assertSame(1, Enrollment::query()->where('status', 'expired')->count());
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_notification_endpoints_only_expose_the_signed_in_participants_records(): void
    {
        $role = Role::query()->firstOrCreate(['name' => 'participant', 'guard_name' => 'web']);
        $participant = User::factory()->create();
        $other = User::factory()->create();
        $participant->assignRole($role);
        $notice = ParticipantNotification::query()->create([
            'participant_id' => $other->id,
            'event_key' => 'private-event',
            'type' => 'completed',
            'data' => ['title' => 'Private'],
        ]);

        $this->actingAs($participant)->getJson('/notifications/count')->assertOk()->assertJson(['unread_count' => 0]);
        $this->actingAs($participant)->patchJson('/notifications/'.$notice->id.'/read')->assertOk();

        $this->assertNull($notice->fresh()->read_at);
    }
}
