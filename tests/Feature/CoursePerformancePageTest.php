<?php

namespace Tests\Feature;

use App\Models\Identity\User;
use App\Services\Course\CoursePerformanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CoursePerformancePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_returns_performance_data_to_authorized_admin(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $result = collect([[
            'course_id' => 7,
            'course_title' => 'Kelas Uji',
            'avg_pre_test' => 50,
            'avg_post_test' => 80,
            'avg_margin' => 30,
            'pre_test_note' => 'Catatan awal',
            'post_test_note' => 'Catatan akhir',
        ]]);
        $service = $this->mock(CoursePerformanceService::class);
        $service->shouldReceive('getPerformance')->once()->andReturn($result);

        $this->actingAs($admin)->get(route('admin.course-performance.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/CoursePerformance/Index')
                ->where('result.0.course_title', 'Kelas Uji')
                ->where('result.0.avg_margin', 30)
                ->where('result.0.pre_test_note', 'Catatan awal')
                ->where('result.0.post_test_note', 'Catatan akhir'));
    }

    public function test_page_requires_result_view_permission(): void
    {
        Role::findOrCreate('viewer', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('viewer');

        $this->actingAs($admin)->get(route('admin.course-performance.index'))->assertForbidden();
    }

    public function test_table_filters_sorts_and_paginates_performance_rows(): void
    {
        Role::findOrCreate('super_admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $this->mock(CoursePerformanceService::class)->shouldReceive('getPerformance')->andReturn(collect([
            ['course_id' => 1, 'course_title' => 'Kelas A', 'avg_margin' => 10],
            ['course_id' => 2, 'course_title' => 'Kelas B', 'avg_margin' => 30],
            ['course_id' => 3, 'course_title' => 'Pelatihan C', 'avg_margin' => 20],
        ]));
        $this->actingAs($admin)->getJson(route('admin.course-performance.index', [
            'draw' => 1, 'start' => 0, 'length' => 1,
            'search' => ['value' => 'Kelas'],
            'columns' => [
                ['data' => 'course_title', 'name' => 'course_title', 'searchable' => 'true', 'orderable' => 'true'],
                ['data' => 'avg_margin', 'name' => 'avg_margin', 'searchable' => 'false', 'orderable' => 'true'],
            ],
            'order' => [['column' => 1, 'dir' => 'desc']],
        ]))->assertOk()->assertJsonPath('recordsTotal', 3)->assertJsonPath('recordsFiltered', 2)
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.course_title', 'Kelas B');
    }
}
