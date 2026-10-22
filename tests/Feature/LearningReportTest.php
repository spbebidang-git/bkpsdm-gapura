<?php

namespace Tests\Feature;

use App\Models\Course\Activity\Activity;
use App\Models\Course\Course;
use App\Models\Course\Schedule;
use App\Models\Course\Trainer;
use App\Models\Identity\Opd;
use App\Models\Identity\User;
use App\Models\Learning\Enrollment;
use App\Models\Learning\Learn;
use App\Models\Learning\LearnItem;
use App\Services\Learning\ParticipantEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LearningReportTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        Role::findOrCreate($role, 'web');
        $user = User::factory()->create();
        $user->assignRole($role);
        return $user;
    }

    private function enroll(User $participant, Course $course): Enrollment
    {
        $schedule = $course->schedules()->first() ?? Schedule::create(['course_id' => $course->id]);
        return app(ParticipantEnrollmentService::class)->activate($course, $schedule, (string) $participant->id);
    }

    public function test_report_groups_people_and_classes_and_keeps_activity_details_on_separate_pages(): void
    {
        $admin = $this->userWithRole('super_admin');
        $first = $this->userWithRole('participant');
        $first->update(['nip' => '198001010101001']);
        $second = $this->userWithRole('participant');
        $course = Course::factory()->open()->create();
        $emptyCourse = Course::factory()->open()->create();
        $enrollment = $this->enroll($first, $course);
        $this->enroll($first, $emptyCourse);
        $this->enroll($second, $course);
        $activity = Activity::create(['course_id' => $course->id, 'title' => 'Post-test', 'type' => 'post-test']);
        Activity::create(['course_id' => $course->id, 'title' => 'Video', 'type' => 'video']);
        $learn = Learn::create(['participant_id' => $first->id, 'course_id' => $course->id,
            'enrollment_id' => $enrollment->id, 'finished_at' => now()]);
        LearnItem::create(['learn_id' => $learn->id, 'activity_id' => $activity->id,
            'title' => $activity->title, 'type' => $activity->type, 'status' => 'done',
            'response' => serialize([['score' => 80, 'is_correct' => true, 'answer' => 'Secret answer']])]);

        $this->actingAs($admin)->get(route('admin.report'))->assertInertia(fn (Assert $page) => $page
            ->component('Report')->where('view', 'participant')
            ->where('summary.total_participants', 2)->where('summary.total_courses', 2)
            ->where('summary.total_enrollments', 3));
        $participants = $this->getJson(route('admin.report.groups', ['group_by' => 'participant']))
            ->assertOk()->assertJsonCount(2, 'data')->json('data');
        $firstGroup = collect($participants)->firstWhere('group_id', $first->id);
        $this->assertSame(2, $firstGroup['course_count']);
        $this->assertSame(1, $firstGroup['completed_count']);
        $this->getJson(route('admin.report.groups', ['group_by' => 'participant', 'search' => ['value' => '198001010101001']]))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.group_id', $first->id);
        $this->getJson(route('admin.report.groups', ['group_by' => 'course']))->assertOk()->assertJsonCount(2, 'data');

        $this->get(route('admin.report.participant', $first->id))->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Report/Show')->where('entity.type', 'participant')
            ->where('filters.participant_id', $first->id)->has('courses', 2));
        $this->get(route('admin.report.course', $course->id))->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Report/Show')->where('entity.type', 'course')->has('participants', 2));
        $backUrl = route('admin.report', ['view' => 'course', 'course_id' => $course->id]);
        $this->get(route('admin.report.course', ['course' => $course->id, 'return_to' => $backUrl]))
            ->assertInertia(fn (Assert $page) => $page->where('backUrl', $backUrl));
        $details = $this->getJson(route('admin.report.data', ['participant_id' => $first->id]))
            ->assertOk()->assertJsonCount(2, 'data');
        $this->assertStringNotContainsString('Secret answer', $details->getContent());
    }

    public function test_archived_enrollments_are_excluded_from_report_groups(): void
    {
        $admin = $this->userWithRole('super_admin');
        $participant = $this->userWithRole('participant');
        $course = Course::factory()->open()->create();
        $this->enroll($participant, $course)->delete();
        $this->actingAs($admin)->getJson(route('admin.report.groups', ['group_by' => 'participant']))
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_trainer_report_and_participant_details_are_limited_to_assigned_courses(): void
    {
        $trainerUser = $this->userWithRole('trainer');
        $trainerUser->givePermissionTo(Permission::findOrCreate('result.view', 'web'));
        $opd = Opd::create(['code' => 'REPORT', 'name' => 'OPD laporan', 'is_active' => true]);
        $trainerUser->update(['opd_id' => $opd->id]);
        $trainer = Trainer::create(['user_id' => $trainerUser->id, 'name' => $trainerUser->name]);
        $participant = $this->userWithRole('participant');
        $otherParticipant = $this->userWithRole('participant');
        $assigned = Course::factory()->open()->create();
        $other = Course::factory()->open()->create();
        $assigned->trainers()->attach($trainer->id);
        $this->enroll($participant, $assigned);
        $this->enroll($participant, $other);
        $this->enroll($otherParticipant, $other);

        $this->actingAs($trainerUser)->getJson(route('admin.report.groups', ['group_by' => 'course']))
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.group_id', $assigned->id);
        $this->get(route('admin.report.participant', $participant->id))->assertInertia(fn (Assert $page) => $page
            ->has('courses', 1)->where('courses.0.id', $assigned->id));
        $this->get(route('admin.report.participant', $otherParticipant->id))->assertNotFound();
    }
}
