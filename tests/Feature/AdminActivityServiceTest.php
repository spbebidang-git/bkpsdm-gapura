<?php

namespace Tests\Feature;

use App\Http\Middleware\IsAdminMiddleware;
use App\Models\Course\Activity\Activity;
use App\Models\Course\Activity\Assessment\Question;
use App\Models\Course\Activity\Interactive;
use App\Models\Course\Course;
use App\Repositories\Course\Activity\Contracts\TaskRepositoryInterface;
use App\Services\Course\Activity\AdminActivityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AdminActivityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_companion_failure_rolls_back_new_activity(): void
    {
        $course = Course::factory()->draft()->create();
        $tasks = Mockery::mock(TaskRepositoryInterface::class);
        $tasks->shouldReceive('create')->once()->andThrow(new RuntimeException('Storage failed'));
        $this->app->instance(TaskRepositoryInterface::class, $tasks);

        try {
            app(AdminActivityService::class)->storeTask([
                'title' => 'Rollback task', 'value' => 10, 'description' => 'Instructions',
            ], $course);
            $this->fail('The companion failure must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Storage failed', $exception->getMessage());
        }

        $this->assertDatabaseMissing('activities', ['course_id' => $course->id, 'title' => 'Rollback task']);
    }

    public function test_companion_failure_rolls_back_activity_update(): void
    {
        $course = Course::factory()->draft()->create();
        $activity = Activity::create(['course_id' => $course->id, 'title' => 'Original', 'type' => 'task', 'value' => 5]);
        $tasks = Mockery::mock(TaskRepositoryInterface::class);
        $tasks->shouldReceive('findByActivity')->once()->with($activity->id)->andThrow(new RuntimeException('Storage failed'));
        $this->app->instance(TaskRepositoryInterface::class, $tasks);

        try {
            app(AdminActivityService::class)->updateTask($course, $activity, [
                'title' => 'Changed', 'value' => 10, 'description' => 'Instructions',
            ]);
            $this->fail('The companion failure must propagate.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Storage failed', $exception->getMessage());
        }

        $this->assertSame('Original', $activity->fresh()->title);
        $this->assertEquals(5, $activity->fresh()->value);
    }

    public function test_end_meeting_route_persists_only_the_selected_meeting(): void
    {
        $this->withoutMiddleware(IsAdminMiddleware::class);
        $course = Course::factory()->draft()->create(['is_locked' => false]);
        $meetings = collect(['First', 'Other'])->map(function ($title) use ($course) {
            $activity = Activity::create(['course_id' => $course->id, 'title' => $title, 'type' => 'meeting']);

            return Interactive::create(['activity_id' => $activity->id, 'type' => 'meeting', 'description' => 'Meeting', 'url' => 'https://example.com/meeting', 'duration' => 60, 'end_meeting' => 0]);
        });

        $this->post(route('admin.course.activity.meeting.end-meeting', [$course->slug, $meetings[0]->activity_id]))
            ->assertRedirect(route('admin.course.activities.index', $course->slug))->assertSessionHas('success');
        $this->assertEquals(1, $meetings[0]->fresh()->end_meeting);
        $this->assertEquals(0, $meetings[1]->fresh()->end_meeting);
    }

    public function test_invalid_video_request_does_not_create_an_activity(): void
    {
        $this->withoutMiddleware(IsAdminMiddleware::class);
        $course = Course::factory()->draft()->create(['is_locked' => false]);
        $this->postJson(route('admin.course.activity.video.store', $course->slug), [
            'title' => 'Invalid video', 'description' => 'Description', 'value' => 10,
            'url' => 'https://example.com/video', 'duration' => 0,
        ])->assertUnprocessable()->assertJsonValidationErrors(['url', 'duration']);
        $this->assertDatabaseMissing('activities', ['course_id' => $course->id, 'title' => 'Invalid video']);
    }

    public function test_post_test_update_preserves_repeatability_and_instruction_input(): void
    {
        $this->withoutMiddleware(IsAdminMiddleware::class);
        $course = Course::factory()->draft()->create(['is_locked' => false]);
        $activity = Activity::create(['course_id' => $course->id, 'title' => 'Post test', 'type' => 'post-test', 'value' => 5, 'timer' => 60]);
        $question = Question::create([
            'activity_id' => $activity->id, 'type' => 'post', 'description' => 'Original', 'min_score' => 50, 'is_loop' => false,
        ]);

        $this->put(route('admin.course.activity.post-test.update', [$course->slug, $activity->id]), [
            'title' => 'Updated', 'description' => 'Updated description', 'value' => 10,
            'timer' => 2, 'min_score' => 75, 'instruction' => 'Keep this instruction', 'is_loop' => true,
        ])->assertRedirect(route('admin.course.activities.index', $course->slug))->assertSessionHas('success');

        $this->assertEquals(1, $question->fresh()->is_loop);
        $this->assertSame('Keep this instruction', $activity->fresh()->instruction);
        $this->assertEquals(120, $activity->fresh()->timer);
    }
}
