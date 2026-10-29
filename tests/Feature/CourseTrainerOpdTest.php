<?php

namespace Tests\Feature;

use App\Models\Course\Course;
use App\Models\Course\Trainer;
use App\Models\Identity\EmployeeStatus;
use App\Models\Identity\Opd;
use App\Models\Identity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CourseTrainerOpdTest extends TestCase
{
    use RefreshDatabase;

    private function opd(string $code, bool $active = true): Opd
    {
        return Opd::create(['code' => $code, 'name' => 'OPD '.$code, 'is_active' => $active]);
    }

    private function admin(): User
    {
        Role::findOrCreate('super_admin', 'web');
        $user = User::factory()->create();
        $user->assignRole('super_admin');
        $this->actingAs($user);
        return $user;
    }

    private function payload(Course $course, Trainer $trainer, array $opds): array
    {
        $status = EmployeeStatus::firstOrCreate(['title' => 'PNS']);
        return [
            'type' => 'unschedule', 'category' => $course->category_id,
            'employee_status' => [$status->id], 'title' => $course->title,
            'short_description' => $course->short_description, 'description' => $course->description,
            'teaser_video' => '', 'expired' => 72, 'jam_pelajaran' => 8, 'status' => 'draft',
            'trainers' => [$trainer->id], 'visibility' => 'private', 'opd_ids' => $opds,
        ];
    }

    public function test_publication_requires_at_least_one_non_deleted_activity(): void
    {
        $this->admin();
        $opd = $this->opd('publish');
        $trainer = Trainer::create(['name' => 'Pemateri', 'opd_id' => $opd->id]);

        foreach (['unschedule', 'schedule'] as $type) {
            $course = Course::factory()->draft()->create(['access_mode' => $type === 'schedule' ? 'scheduled' : 'open']);
            $course->schedules()->create([]);
            $payload = [...$this->payload($course, $trainer, [$opd->id]), 'type' => $type, 'status' => 'publish'];
            $url = route('admin.course.update', $course->slug);

            $this->putJson($url, $payload)->assertUnprocessable()->assertJsonValidationErrors('status');
            $this->assertSame('draft', $course->fresh()->status);

            $activity = \App\Models\Course\Activity\Activity::create([
                'course_id' => $course->id, 'type' => 'video', 'title' => 'Materi',
            ]);
            $activity->delete();
            $this->putJson($url, $payload)->assertUnprocessable()->assertJsonValidationErrors('status');
            $activity->restore();
            $this->put($url, $payload)->assertSessionHasNoErrors();
            $this->assertSame('publish', $course->fresh()->status);
        }

        $this->postJson(route('admin.course.store'), [
            ...$payload, 'thumbnail' => UploadedFile::fake()->image('kelas.jpg'),
        ])->assertUnprocessable()->assertJsonValidationErrors('status');
    }

    public function test_private_targets_are_limited_to_selected_trainer_opds_and_public_clears_targets(): void
    {
        $admin = $this->admin();
        $a = $this->opd('001'); $b = $this->opd('002'); $c = $this->opd('003');
        $first = Trainer::create(['name' => 'Pemateri A', 'opd_id' => $a->id]);
        $second = Trainer::create(['name' => 'Pemateri B', 'opd_id' => $b->id]);
        $course = Course::factory()->open()->draft()->create();
        $payload = [...$this->payload($course, $first, [$a->id, $b->id]), 'trainers' => [$first->id, $second->id]];
        $this->put(route('admin.course.update', $course->slug), $payload)
            ->assertRedirect(route('admin.course.index'))->assertSessionHasNoErrors();
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $course->fresh()->opds->pluck('id')->all());
        $this->assertSame($admin->id, $course->fresh()->modified_by);
        $this->assertCount(2, $course->fresh()->trainers);
        $this->get(route('admin.course.edit', $course->slug))->assertInertia(fn (Assert $page) => $page
            ->has('opds', 3)->has('course.opds', 2)->has('trainers', 2));
        $this->putJson(route('admin.course.update', $course->slug), [...$payload, 'opd_ids' => [$c->id]])
            ->assertUnprocessable()->assertJsonValidationErrors('opd_ids');
        $this->put(route('admin.course.update', $course->slug), [...$payload, 'visibility' => 'public'])
            ->assertSessionHasNoErrors();
        $this->assertSame('public', $course->fresh()->visibility);
        $this->assertCount(0, $course->fresh()->opds);
        $this->put(route('admin.course.update', $course->slug), $payload)->assertSessionHasNoErrors();
        $this->assertCount(2, $course->fresh()->opds);
    }

    public function test_private_requires_active_unique_opds_statuses_and_active_trainers_without_partial_changes(): void
    {
        $this->admin();
        $a = $this->opd('001'); $inactive = $this->opd('002', false);
        $trainer = Trainer::create(['name' => 'Pemateri A', 'opd_id' => $a->id]);
        $course = Course::factory()->open()->draft()->create();
        $payload = $this->payload($course, $trainer, [$a->id]);
        foreach ([['opd_ids' => []], ['opd_ids' => [$inactive->id]], ['opd_ids' => [$a->id, $a->id]], ['opd_ids' => [99999]], ['employee_status' => []]] as $invalid) {
            $this->putJson(route('admin.course.update', $course->slug), [...$payload, 'title' => 'Tidak boleh tersimpan', ...$invalid])
                ->assertUnprocessable();
            $this->assertSame($course->title, $course->fresh()->title);
            $this->assertCount(0, $course->fresh()->opds);
        }
        $trainer->update(['opd_id' => $inactive->id]);
        $this->putJson(route('admin.course.update', $course->slug), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('trainers');
    }

    public function test_trainer_can_create_public_and_cross_opd_private_and_only_manage_assigned_courses(): void
    {
        $a = $this->opd('001'); $b = $this->opd('002');
        $role = Role::findOrCreate('trainer', 'web');
        foreach (['course.create', 'course.update', 'course.view', 'course.activity.manage'] as $permission) {
            $role->givePermissionTo(Permission::findOrCreate($permission, 'web'));
        }
        $user = User::factory()->create(['opd_id' => $a->id]);
        $user->assignRole($role);
        $trainer = Trainer::create(['name' => $user->name, 'user_id' => $user->id]);
        $course = Course::factory()->open()->draft()->create();
        $course->trainers()->attach($trainer->id);
        $payload = $this->payload($course, $trainer, [$a->id]);
        $this->actingAs($user)->put(route('admin.course.update', $course->slug), $payload)
            ->assertSessionHasNoErrors();
        $this->assertSame([$a->id], $course->fresh()->opds->pluck('id')->all());
        $this->get(route('admin.course.activities.index', $course->slug))->assertOk();
        $course->update(['access_mode' => 'scheduled']);
        $this->get(route('admin.course.schedules.index', $course->slug))->assertOk();
        $this->post(route('admin.course.schedules.store', $course->slug), ['date' => now()->addDay()->toDateString(), 'time_from' => '09:00', 'time_to' => '11:00', 'quota' => 20])->assertSessionHasNoErrors();
        $this->get(route('admin.course.index'), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()->assertJsonPath('data.0.id', $course->id);
        $other = Course::factory()->open()->draft()->create();
        $this->get(route('admin.course.edit', $other->slug))->assertForbidden();
        $this->get(route('admin.course.activities.index', $other->slug))->assertForbidden();
        $this->get(route('admin.course.schedules.index', $other->slug))->assertForbidden();
        $this->put(route('admin.course.update', $other->slug), $payload)->assertForbidden();

        $directory = storage_path('framework/testing/course-visibility-'.Str::uuid());
        $this->app->usePublicPath($directory);
        try {
            foreach (['public', 'private'] as $visibility) {
                $this->post(route('admin.course.store'), [
                    ...$payload, 'title' => 'Kelas '.$visibility, 'visibility' => $visibility,
                    'trainers' => [], 'opd_ids' => [$a->id], 'thumbnail' => UploadedFile::fake()->image('kelas.jpg'),
                ])->assertRedirect(route('admin.course.index'))->assertSessionHasNoErrors();
                $created = Course::where('title', 'Kelas '.$visibility)->firstOrFail();
                $this->assertSame($visibility, $created->visibility);
                $this->assertSame($user->id, $created->modified_by);
                $this->assertSame([$trainer->id], $created->trainers->pluck('id')->all());
                $this->assertCount($visibility === 'private' ? 1 : 0, $created->opds);
            }
        } finally {
            File::deleteDirectory($directory);
        }
    }
    public function test_target_sync_failure_rolls_back_class_fields_editor_and_relations(): void
    {
        $course = Course::factory()->open()->draft()->create();
        $this->admin();
        $trainer = Trainer::create(['name' => 'Pemateri A', 'opd_id' => $this->opd('001')->id]);
        $payload = [...$this->payload($course, $trainer, [99999]), 'title' => 'Gagal disimpan'];
        try {
            app(\App\Services\Course\CourseService::class)->updateCourse($course, $payload);
            $this->fail('Invalid target must fail the transaction.');
        } catch (\Illuminate\Database\QueryException $exception) {
            $this->assertNotEmpty($exception->getMessage());
        }
        $this->assertNotSame('Gagal disimpan', $course->fresh()->title);
        $this->assertNull($course->fresh()->modified_by);
        $this->assertCount(0, $course->fresh()->opds);
        $this->assertCount(0, $course->fresh()->employeeStatus);
        $this->assertCount(0, $course->fresh()->trainers);
    }

}
