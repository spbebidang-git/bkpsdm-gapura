<?php

namespace Tests\Feature;

use App\Models\Course\Course;
use App\Models\Identity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CourseActivityUnlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_super_admin_can_unlock_a_locked_course(): void
    {
        $superAdmin = User::factory()->create();
        Role::findOrCreate('super_admin', 'web');
        $superAdmin->assignRole('super_admin');

        $administrator = User::factory()->create();
        $role = Role::findOrCreate('administrator', 'web');
        $role->givePermissionTo(Permission::findOrCreate('course.update', 'web'));
        $role->givePermissionTo(Permission::findOrCreate('course.activity.manage', 'web'));
        $administrator->assignRole($role);

        $course = Course::factory()->draft()->create(['is_locked' => true]);
        $url = route('admin.course.unlock', $course->slug);

        $this->actingAs($administrator)->get(route('admin.course.activities.index', $course->slug))
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Course/Activities')->where('canUnlock', false));
        $this->actingAs($administrator)->post($url)->assertForbidden();
        $this->assertTrue((bool) $course->fresh()->is_locked);

        $this->actingAs($superAdmin)->get(route('admin.course.activities.index', $course->slug))
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Course/Activities')->where('canUnlock', true));
        $this->actingAs($superAdmin)->post($url)
            ->assertRedirect(route('admin.course.activities.index', $course->slug))
            ->assertSessionHas('success', 'Kegiatan berhasil dibuka kuncinya.');
        $this->assertFalse((bool) $course->fresh()->is_locked);
    }
}
