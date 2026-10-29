<?php

namespace Tests\Feature;

use App\Models\Course\Activity\Activity;
use App\Models\Course\Course;
use App\Models\Course\Schedule;
use App\Models\Identity\EmployeeStatus;
use App\Models\Identity\Opd;
use App\Models\Identity\User;
use App\Models\Learning\Enrollment;
use App\Models\Learning\Learn;
use App\Services\Course\CourseAudience;
use App\Services\Course\CourseService;
use App\Services\Learning\ParticipantEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CourseVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function opd(string $code): Opd
    {
        return Opd::create(['code' => $code, 'name' => 'OPD '.$code, 'is_active' => true]);
    }

    private function participant(?Opd $opd, ?EmployeeStatus $status): User
    {
        Role::findOrCreate('participant', 'web');
        $user = User::factory()->create(['opd_id' => $opd?->id, 'employee_status_id' => $status?->id]);
        $user->assignRole('participant');
        return $user;
    }

    private function course(string $visibility, array $opds, EmployeeStatus $status): Course
    {
        $course = Course::factory()->open()->create(['visibility' => $visibility]);
        $course->opds()->sync($opds);
        $course->employeeStatus()->sync([$status->id]);
        $course->schedules()->create([]);
        return $course;
    }

    public function test_catalog_filters_public_and_private_classes_for_eligible_participants(): void
    {
        $opd = $this->opd('filter');
        $status = EmployeeStatus::create(['title' => 'PNS']);
        $user = $this->participant($opd, $status);
        $public = $this->course('public', [], $status);
        $private = $this->course('private', [$opd->id], $status);

        foreach (['public' => $public, 'private' => $private] as $visibility => $course) {
            $this->actingAs($user)->get(route('course.index', ['visibility' => $visibility]))
                ->assertInertia(fn (Assert $page) => $page
                    ->where('filters.visibility', $visibility)
                    ->has('courses', 1)
                    ->where('courses.0.id', $course->id));
        }
    }

    public function test_guests_only_see_public_published_classes_and_cannot_open_private_urls(): void
    {
        $opd = $this->opd('001');
        $status = EmployeeStatus::create(['title' => 'PNS']);
        $public = $this->course('public', [], $status);
        $private = $this->course('private', [$opd->id], $status);
        $draft = $this->course('public', [], $status);
        $draft->update(['status' => 'draft']);
        $this->get(route('course.index'))->assertInertia(fn (Assert $page) => $page
            ->has('courses', 1)->where('courses.0.id', $public->id));
        $this->get(route('home'))->assertInertia(fn (Assert $page) => $page
            ->has('courses', 1)->where('courses.0.id', $public->id));
        $this->get(route('course.show', $public->slug))->assertOk();
        $this->get(route('course.show', $private->slug))->assertNotFound();
        $this->get(route('course.show', $draft->slug))->assertNotFound();
        $activity = Activity::create(['course_id' => $public->id, 'type' => 'video', 'title' => 'Materi']);
        $this->get(route('participant.learn.instruction.index', [$public->slug, $public->schedules->first()->id, $activity->id]))
            ->assertRedirect();
        $this->assertDatabaseCount('enrollments', 0);
    }

    public function test_private_requires_both_status_and_any_of_the_target_opds(): void
    {
        $a = $this->opd('001'); $b = $this->opd('002'); $c = $this->opd('003');
        $pns = EmployeeStatus::create(['title' => 'PNS']);
        $pppk = EmployeeStatus::create(['title' => 'PPPK']);
        $private = $this->course('private', [$a->id, $b->id], $pns);
        $public = $this->course('public', [], $pns);
        foreach ([[$a, $pns, true], [$b, $pns, true], [$c, $pns, false], [$a, $pppk, false], [null, $pns, false], [$a, null, false]] as [$opd, $status, $allowed]) {
            $user = $this->participant($opd, $status);
            $this->actingAs($user)->get(route('course.show', $private->slug))->assertStatus($allowed ? 200 : 404);
            $this->assertSame($allowed, app(CourseAudience::class)->canEnroll($private, $user));
            $ids = app(CourseService::class)->getAllCourse(user: $user)->pluck('id');
            $this->assertSame($allowed, $ids->contains($private->id));
            $this->assertTrue($ids->contains($public->id));
        }
        $private->employeeStatus()->attach($pppk->id);
        $this->assertTrue(app(CourseAudience::class)->canEnroll($private->fresh(), $this->participant($a, $pppk)));
        $b->update(['is_active' => false]);
        $this->assertFalse(app(CourseAudience::class)->canEnroll($private->fresh(), $this->participant($b, $pns)));
    }

    public function test_enrollment_checks_status_and_schedule_before_creating_progress(): void
    {
        $a = $this->opd('001'); $b = $this->opd('002');
        $pns = EmployeeStatus::create(['title' => 'PNS']);
        $pppk = EmployeeStatus::create(['title' => 'PPPK']);
        $public = $this->course('public', [], $pns);
        $private = $this->course('private', [$a->id], $pns);
        $wrong = $this->participant($b, $pppk);
        $url = route('course.store', [$public->slug, $public->schedules->first()->id]);
        $this->actingAs($wrong)->post($url)->assertForbidden();
        $eligible = $this->participant($b, $pns);
        $this->actingAs($eligible)->post(route('course.store', [$private->slug, $private->schedules->first()->id]))->assertForbidden();
        $this->post(route('course.store', [$public->slug, $private->schedules->first()->id]))->assertNotFound();
        $this->assertDatabaseCount('enrollments', 0);
        $this->assertDatabaseCount('learns', 0);
        $this->post($url)->assertRedirect(route('course.show', $public->slug));
        $this->assertDatabaseHas('enrollments', ['course_id' => $public->id, 'participant_id' => $eligible->id]);
    }

    public function test_json_activation_returns_success_without_redirecting_to_course_detail(): void
    {
        $status = EmployeeStatus::create(['title' => 'PNS']);
        $course = $this->course('public', [], $status);
        $user = $this->participant(null, $status);
        $schedule = $course->schedules->first();
        $url = route('course.store', [$course->slug, $schedule->id]);

        $this->actingAs($user)->postJson($url)
            ->assertOk()->assertHeaderMissing('Location')
            ->assertJson(['message' => 'Kelas berhasil diaktifkan.']);
        $enrollment = Enrollment::sole();

        // Retrying a completed request must preserve the original access period.
        $this->postJson($url)->assertOk();
        $this->assertDatabaseCount('enrollments', 1);
        $this->assertSame($enrollment->access_ends_at->toDateTimeString(), $enrollment->fresh()->access_ends_at->toDateTimeString());
        $this->assertSame(1, $schedule->participants()->whereKey($user->id)->count());
        $this->get(route('course.show', $course->slug))->assertOk()
            ->assertInertia(fn (Assert $page) => $page->where('schedules.0.is_active', 1));
    }

    public function test_activation_failure_rolls_back_and_returns_an_error_status(): void
    {
        $status = EmployeeStatus::create(['title' => 'PNS']);
        $course = $this->course('public', [], $status);
        $user = $this->participant(null, $status);
        $schedule = $course->schedules->first();
        $this->partialMock(CourseService::class, function ($mock) {
            $mock->shouldReceive('generatePrePostTestVariant')->once()
                ->andThrow(new \RuntimeException('Variant generation failed'));
        });

        $this->actingAs($user)->postJson(route('course.store', [$course->slug, $schedule->id]))
            ->assertStatus(500)
            ->assertJson(['message' => 'Aktivasi kelas belum berhasil. Silakan coba lagi dalam beberapa saat.']);
        $this->assertDatabaseCount('enrollments', 0);
        $this->assertSame(0, $schedule->participants()->count());
    }

    public function test_existing_participants_keep_access_after_profile_changes_without_extending_expiry(): void
    {
        $a = $this->opd('001'); $b = $this->opd('002');
        $pns = EmployeeStatus::create(['title' => 'PNS']);
        $course = $this->course('private', [$a->id], $pns);
        $user = $this->participant($a, $pns);
        $schedule = $course->schedules->first();
        $schedule->participants()->attach($user->id);
        $enrollment = app(ParticipantEnrollmentService::class)->activate($course, $schedule, $user->id);
        $ends = $enrollment->access_ends_at->toDateTimeString();
        $activity = Activity::create(['course_id' => $course->id, 'type' => 'video', 'title' => 'Materi']);
        \App\Models\Course\Activity\Interactive::create(['activity_id' => $activity->id, 'type' => 'video', 'url' => 'https://www.youtube.com/embed/test', 'duration' => 60]);
        $user->update(['opd_id' => $b->id, 'employee_status_id' => null]);
        $course->opds()->sync([$b->id]);
        $this->actingAs($user)->get(route('course.show', $course->slug))->assertOk();
        $learnUrl = route('participant.learn.instruction.index', [$course->slug, $schedule->id, $activity->id]);
        $this->get($learnUrl)->assertOk();
        $this->post(route('course.store', [$course->slug, $schedule->id]))->assertRedirect();
        $this->assertSame($ends, $enrollment->fresh()->access_ends_at->toDateTimeString());
        $enrollment->update(['access_ends_at' => now()->subHour()]);
        $this->get($learnUrl)->assertRedirect(route('participant.course.show', $course->slug));
        $unregistered = $this->participant($a, $pns);
        $this->actingAs($unregistered)->get($learnUrl)->assertNotFound();
        $this->get(route('participant.rating.index', [$course->slug, $schedule->id]))->assertNotFound();
        $this->get(route('participant.survey.index', [$course->slug, $schedule->id]))->assertNotFound();
        config(['participant.enrollment_access_enforced' => false]);
        $this->get($learnUrl)->assertNotFound();
    }

    public function test_legacy_registration_migration_preserves_dates_progress_and_is_idempotent(): void
    {
        $pns = EmployeeStatus::create(['title' => 'PNS']);
        $course = $this->course('private', [$this->opd('001')->id], $pns);
        $user = $this->participant(null, null);
        $schedule = $course->schedules->first();
        $registered = now()->subDays(10)->startOfSecond();
        $schedule->participants()->attach($user->id, ['created_at' => $registered]);
        $learn = Learn::create(['course_id' => $course->id, 'participant_id' => $user->id, 'created_at' => $registered]);
        $migration = require database_path('migrations/2026_10_21_000003_preserve_legacy_course_registrations.php');
        $migration->up(); $migration->up();
        $enrollment = Enrollment::sole();
        $this->assertSame($registered->toDateTimeString(), $enrollment->enrolled_at->toDateTimeString());
        $this->assertSame($registered->copy()->addHours(72)->toDateTimeString(), $enrollment->access_ends_at->toDateTimeString());
        $this->assertSame($enrollment->id, $learn->fresh()->enrollment_id);
        $this->assertTrue(app(CourseAudience::class)->isRegistered($course, $user));
        $this->assertFalse(app(ParticipantEnrollmentService::class)->canWrite($enrollment));
    }

    public function test_visibility_migration_maps_legacy_opd_without_changing_course_history(): void
    {
        $opd = $this->opd('001');
        $public = Course::factory()->create(['opd_id' => null]);
        $private = Course::factory()->create(['opd_id' => $opd->id]);
        $timestamp = $private->getRawOriginal('updated_at');
        $migration = require database_path('migrations/2026_10_21_000002_add_course_visibility_and_target_opds.php');
        $migration->down(); $migration->up();
        $this->assertSame('public', $public->fresh()->visibility);
        $this->assertSame('private', $private->fresh()->visibility);
        $this->assertSame([$opd->id], $private->fresh()->opds->pluck('id')->all());
        $this->assertSame($timestamp, $private->fresh()->getRawOriginal('updated_at'));
    }
    public function test_admin_integration_exposes_visibility_and_all_opds_behind_token_authentication(): void
    {
        $a = $this->opd('001'); $b = $this->opd('002');
        $pns = EmployeeStatus::create(['title' => 'PNS']);
        $course = $this->course('private', [$a->id, $b->id], $pns);
        $url = route('api.v1.courses.show', $course->slug);
        $this->postJson($url)->assertUnauthorized();
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        \Laravel\Sanctum\Sanctum::actingAs($admin, []);
        $this->postJson($url)->assertForbidden();
        \Laravel\Sanctum\Sanctum::actingAs($admin, ['api:read']);
        $this->postJson($url)->assertOk()->assertJsonPath('data.visibility', 'private')
            ->assertJsonCount(2, 'data.opds')->assertJsonCount(2, 'data.opd_ids');
    }

}
