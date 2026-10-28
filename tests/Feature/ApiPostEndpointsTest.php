<?php

namespace Tests\Feature;

use App\Models\Course\Course;
use App\Models\Identity\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiPostEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_data_routes_accept_only_post_and_require_authentication(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->getName() ?? '', 'api.v1.')
                && ! in_array($route->getName(), ['api.v1.login', 'api.v1.logout']));
        $this->assertCount(12, $routes);

        foreach ($routes as $route) {
            $this->assertSame(['POST'], $route->methods());
            $url = '/'.str_replace(['{slug}', '{id}'], ['uji-iop', '01m5z13ng4wev8f9qnw93vksfa'], $route->uri());
            $this->getJson($url)->assertMethodNotAllowed();
            $this->postJson($url)->assertUnauthorized();
        }
    }

    public function test_course_filters_and_pagination_are_read_from_json_body(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['api:read']);
        $course = Course::factory()->open()->create(['title' => 'Pelatihan IOP Pertama']);
        Course::factory()->open()->create(['category_id' => $course->category_id, 'title' => 'Pelatihan IOP Kedua']);
        Course::factory()->create(['title' => 'Tidak Sesuai Filter']);

        $this->postJson('/api/v1/courses', [
            'search' => 'Pelatihan IOP', 'category_id' => $course->category_id,
            'type' => 'unschedule', 'year' => now()->year, 'per_page' => 1, 'page' => 2,
        ])->assertOk()->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 1)->assertJsonPath('meta.total', 2);
    }

    public function test_yearly_endpoint_validates_year_from_json_body(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['api:read']);
        $this->postJson('/api/v1/statistics/yearly', ['year' => 1800])
            ->assertUnprocessable()->assertJsonValidationErrors('year');
        $this->postJson('/api/v1/statistics/yearly', ['year' => 2025])
            ->assertOk()->assertJsonPath('data.year', 2025);
    }

    public function test_category_courses_accepts_ulid_and_keeps_pagination(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['api:read']);
        $course = Course::factory()->create();
        Course::factory()->draft()->create(['category_id' => $course->category_id]);

        $this->postJson('/api/v1/categories/'.$course->category_id.'/courses', ['per_page' => 1])
            ->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('category.id', $course->category_id)
            ->assertJsonPath('data.0.id', $course->id)
            ->assertJsonPath('meta.per_page', 1)->assertJsonPath('meta.total', 1);
    }

    public function test_participant_details_accepts_ulid_and_returns_course_history(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['api:read']);
        \Spatie\Permission\Models\Role::findOrCreate('participant', 'web');
        $participant = User::factory()->create();
        $participant->assignRole('participant');
        $course = Course::factory()->create();
        $schedule = $course->schedules()->create([]);
        $schedule->participants()->attach($participant->id);
        \App\Models\Learning\Learn::create([
            'course_id' => $course->id, 'participant_id' => $participant->id, 'finished_at' => now(),
        ]);

        $this->postJson('/api/v1/participants/'.$participant->id)
            ->assertOk()->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $participant->id)
            ->assertJsonPath('data.total_courses', 1)->assertJsonPath('data.total_completed', 1)
            ->assertJsonPath('data.courses.0.course_id', $course->id);
    }

    public function test_unknown_ulids_return_not_found_instead_of_server_error(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['api:read']);
        \Spatie\Permission\Models\Role::findOrCreate('participant', 'web');
        $id = (string) \Illuminate\Support\Str::ulid();

        $this->postJson('/api/v1/categories/'.$id.'/courses')
            ->assertNotFound()->assertJsonPath('success', false);
        $this->postJson('/api/v1/participants/'.$id)
            ->assertNotFound()->assertJsonPath('success', false);
    }

}
