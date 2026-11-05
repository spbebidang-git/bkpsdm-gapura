<?php

namespace Tests\Feature;

use App\Models\Course\Course;
use App\Models\Identity\EmployeeStatus;
use App\Models\Identity\User;
use App\Models\Learning\Learn;
use App\Repositories\Course\Contracts\CourseRepositoryInterface;
use App\Repositories\Learning\Contracts\LearnRepositoryInterface;
use App\Services\Course\CourseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CourseModalityTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        Role::findOrCreate('super_admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);

        return $user;
    }

    private function kms(): Course
    {
        return Course::factory()->open()->create(['modality' => 'kms', 'visibility' => 'public', 'jam_pelajaran' => 12]);
    }

    public function test_kms_blocks_lms_only_activity_routes(): void
    {
        $this->admin();
        $course = $this->kms();

        $this->get(route('admin.course.activity.pre-test.create', $course->slug))->assertForbidden();
        $this->get(route('admin.course.activity.post-test.create', $course->slug))->assertForbidden();
        $this->get(route('admin.course.activity.task.create', $course->slug))->assertForbidden();
        $this->get(route('admin.course.activity.meeting.create', $course->slug))->assertForbidden();

        $this->get(route('admin.course.activity.video.create', $course->slug))->assertOk();
        $this->get(route('admin.course.activity.literature.create', $course->slug))->assertOk();
    }

    public function test_lms_still_allows_every_activity_type(): void
    {
        $this->admin();
        $course = Course::factory()->open()->create(['modality' => 'lms']);
        Role::findOrCreate('trainer', 'web');
        $course->trainers()->create(['name' => 'Pemateri']);

        foreach (['pre-test', 'post-test', 'task', 'meeting', 'video', 'literature'] as $type) {
            $this->get(route("admin.course.activity.{$type}.create", $course->slug))->assertOk();
        }
    }

    public function test_catalog_separates_kms_and_lms(): void
    {
        $status = EmployeeStatus::create(['title' => 'PNS']);
        $kms = Course::factory()->open()->create(['modality' => 'kms', 'visibility' => 'public', 'status' => 'publish']);
        $lms = Course::factory()->open()->create(['modality' => 'lms', 'status' => 'publish']);
        foreach ([$kms, $lms] as $course) {
            $course->employeeStatus()->sync([$status->id]);
            $course->schedules()->create([]);
        }

        $service = app(CourseService::class);
        $this->assertSame([$kms->id], $service->getAllCourse(['modality' => 'kms'], null, false)->pluck('id')->all());
        $this->assertSame([$lms->id], $service->getAllCourse(['modality' => 'lms'], null, false)->pluck('id')->all());

        // KMS: pilih OPD dulu, materi baru dimuat setelah OPD ditentukan.
        $this->get(route('course.kms'))->assertInertia(fn ($page) => $page
            ->where('filters.modality', 'kms')
            ->has('courses', 0));
        $this->get(route('course.lms'))->assertInertia(fn ($page) => $page->where('filters.modality', 'lms')->where('courses.0.id', $lms->id));
    }

    public function test_kms_is_excluded_from_certificate_queries(): void
    {
        $participant = User::factory()->create();
        $kms = $this->kms();
        $lms = Course::factory()->open()->create(['modality' => 'lms']);
        $kmsLearn = Learn::create(['course_id' => $kms->id, 'participant_id' => $participant->id, 'finished_at' => now()]);
        $lmsLearn = Learn::create(['course_id' => $lms->id, 'participant_id' => $participant->id, 'finished_at' => now()]);

        $ids = app(LearnRepositoryInterface::class)
            ->certificateQuery([])->pluck('id')->all();
        $this->assertSame([$lmsLearn->id], $ids);
        $this->assertNotContains($kmsLearn->id, $ids);
        $this->assertSame([$lms->id], app(CourseRepositoryInterface::class)->certificateCourses()->pluck('id')->all());
    }
}
