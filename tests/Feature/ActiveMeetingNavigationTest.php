<?php

namespace Tests\Feature;

use App\Http\Middleware\CheckParticipantEnrollmentAccess;
use App\Models\Course\Activity\Activity;
use App\Models\Course\Course;
use App\Models\Course\Schedule;
use App\Models\Identity\User;
use App\Models\Learning\Learn;
use App\Models\Learning\LearnItem;
use App\Services\Learning\ParticipantEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Tests\TestCase;

class ActiveMeetingNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function context(): array
    {
        config(['participant.enrollment_access_enforced' => true]);
        $user = User::factory()->create();
        $course = Course::factory()->open()->create();
        $schedule = Schedule::create(['course_id' => $course->id]);
        $enrollment = app(ParticipantEnrollmentService::class)->activate($course, $schedule, (string) $user->id);
        $learn = Learn::create(['participant_id' => $user->id, 'course_id' => $course->id, 'enrollment_id' => $enrollment->id]);
        $meeting = Activity::create(['course_id' => $course->id, 'title' => 'Daring', 'type' => 'meeting']);
        $other = Activity::create(['course_id' => $course->id, 'title' => 'Bacaan', 'type' => 'literature']);
        $item = LearnItem::create(['learn_id' => $learn->id, 'activity_id' => $meeting->id, 'title' => $meeting->title, 'type' => 'meeting', 'status' => 'in_progress']);
        return compact('user', 'course', 'schedule', 'meeting', 'other', 'item');
    }

    private function visit(array $context, Activity $activity, string $name, string $method = 'GET')
    {
        $request = Request::create('/navigation-test', $method);
        $route = new Route([$method], '/navigation-test', fn () => null);
        $route->name($name)->bind($request);
        foreach (['course', 'schedule'] as $key) $route->setParameter($key, $context[$key]);
        $route->setParameter('activity', $activity);
        $request->setRouteResolver(fn () => $route);
        $request->setUserResolver(fn () => $context['user']);
        return (new CheckParticipantEnrollmentAccess())->handle($request, fn () => response('allowed'), $method === 'GET' ? 'read' : 'write');
    }

    public function test_active_meeting_blocks_other_activity_get_and_post_and_own_result(): void
    {
        $context = $this->context();
        $expected = route('participant.learn.index', [$context['course']->slug, $context['schedule']->id, $context['meeting']->id]);
        foreach ([['other', 'participant.learn.instruction.index', 'GET'], ['other', 'participant.learn.store', 'POST'], ['meeting', 'participant.learn.result.store', 'POST']] as [$target, $name, $method]) {
            $response = $this->visit($context, $context[$target], $name, $method);
            $this->assertSame($expected, $response->getTargetUrl());
        }
    }

    public function test_active_meeting_can_be_viewed_and_completed(): void
    {
        $context = $this->context();
        foreach ([['participant.learn.index', 'GET'], ['participant.learn.store', 'POST']] as [$name, $method]) {
            $this->assertSame('allowed', $this->visit($context, $context['meeting'], $name, $method)->getContent());
        }
    }

    public function test_completed_meeting_unlocks_other_activities(): void
    {
        $context = $this->context();
        $context['item']->update(['status' => 'done']);
        $this->assertSame('allowed', $this->visit($context, $context['other'], 'participant.learn.instruction.index')->getContent());
    }

    public function test_another_participant_is_not_locked_by_the_meeting(): void
    {
        $context = $this->context();
        $context['user'] = User::factory()->create();
        app(ParticipantEnrollmentService::class)->activate($context['course'], $context['schedule'], (string) $context['user']->id);
        $this->assertSame('allowed', $this->visit($context, $context['other'], 'participant.learn.instruction.index')->getContent());
    }
    public function test_locked_activity_cannot_be_opened_or_submitted(): void
    {
        $context = $this->context();
        $context['item']->update(['status' => 'done', 'locked_at' => now()]);
        foreach ([['participant.learn.index', 'GET'], ['participant.learn.result.index', 'GET'], ['participant.learn.store', 'POST'], ['participant.learn.result.store', 'POST']] as [$name, $method]) {
            $response = $this->visit($context, $context['meeting'], $name, $method);
            $this->assertSame(route('participant.learn.instruction.index', [$context['course']->slug, $context['schedule']->id, $context['other']->id]), $response->getTargetUrl());
        }
    }

    public function test_next_on_result_locks_the_completed_activity(): void
    {
        $context = $this->context();
        $context['item']->update(['status' => 'done']);
        \Spatie\Permission\Models\Role::findOrCreate('participant', 'web');
        $context['user']->assignRole('participant');
        $response = $this->actingAs($context['user'])->post(route('participant.learn.result.store', [
            $context['course']->slug, $context['schedule']->id, $context['meeting']->id,
        ]));
        $response->assertRedirect();
        $this->assertNotNull($context['item']->fresh()->locked_at);
    }

    public function test_next_cannot_lock_an_unfinished_activity(): void
    {
        $context = $this->context();
        $context['item']->update(['status' => 'available']);
        \Spatie\Permission\Models\Role::findOrCreate('participant', 'web');
        $context['user']->assignRole('participant');
        $this->actingAs($context['user'])->post(route('participant.learn.result.store', [
            $context['course']->slug, $context['schedule']->id, $context['meeting']->id,
        ]))->assertRedirect(route('participant.learn.index', [
            $context['course']->slug, $context['schedule']->id, $context['meeting']->id,
        ]))->assertSessionHasErrors('error');
        $this->assertNull($context['item']->fresh()->locked_at);
    }

}
