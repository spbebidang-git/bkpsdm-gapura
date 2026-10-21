<?php

namespace Tests\Feature;

use App\Models\Course\Activity\Activity;
use App\Models\Course\Course;
use App\Models\Identity\User;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\CourseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CourseModifierTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_and_activity_keep_the_last_authenticated_editor(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $first = User::factory()->create(['name' => 'Admin Pertama']);
        $second = User::factory()->create(['name' => 'Admin Kedua']);
        $first->assignRole('super_admin');
        $second->assignRole('super_admin');

        $course = Course::factory()->draft()->create();
        $this->assertNull($course->modified_by);

        $this->actingAs($first);
        $course->update(['title' => 'Kelas baru']);
        $activity = Activity::create([
            'course_id' => $course->id,
            'type' => 'video',
            'title' => 'Video pertama',
            'value' => 10,
        ]);
        $this->assertSame($first->id, $course->fresh()->modified_by);
        $this->assertSame($first->id, $activity->fresh()->modified_by);

        $this->actingAs($second);
        $course->update(['status' => 'publish']);
        $activity->update(['title' => 'Video diperbarui']);
        $this->assertSame($second->id, $course->fresh()->modified_by);
        $this->assertSame($second->id, $activity->fresh()->modified_by);
        $this->assertSame('Admin Kedua', $course->fresh()->modifier->name);
        $this->assertSame('Admin Kedua', $activity->fresh()->modifier->name);

        $this->assertSame('Admin Kedua', app(CourseService::class)->getByAdmin()->firstWhere('id', $course->id)['modifier_name']);
        $this->get(route('admin.course.activities.index', $course->slug))
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Course/Activities')
                ->where('activities.0.modifier_name', 'Admin Kedua'));
        $this->assertSame('Admin Kedua', app(ActivityService::class)->getActivityByCourse($course->id)->first()['modifier_name']);
    }
}
