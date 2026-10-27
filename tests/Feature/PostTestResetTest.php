<?php

namespace Tests\Feature;

use App\Models\Course\Activity\Activity;
use App\Models\Course\Activity\Assessment\Question;
use App\Models\Course\Activity\Assessment\QuestionItem;
use App\Models\Course\Activity\Assessment\QuestionOption;
use App\Models\Course\Activity\Assessment\QuestionVariant;
use App\Models\Course\Course;
use App\Models\Course\Schedule;
use App\Models\Identity\User;
use App\Models\Learning\Learn;
use App\Models\Learning\LearnItem;
use App\Services\Learning\ParticipantEnrollmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PostTestResetTest extends TestCase
{
    use RefreshDatabase;

    private function context(bool $repeatable): array
    {
        Role::findOrCreate('participant', 'web');
        $participant = User::factory()->create();
        $participant->assignRole('participant');
        $course = Course::factory()->open()->create();
        $schedule = Schedule::create(['course_id' => $course->id]);
        $enrollment = app(ParticipantEnrollmentService::class)->activate($course, $schedule, (string) $participant->id);
        $learn = Learn::create(['participant_id' => $participant->id, 'course_id' => $course->id, 'enrollment_id' => $enrollment->id]);

        $first = Activity::create(['course_id' => $course->id, 'type' => 'literature', 'title' => 'Materi', 'sort_order' => 1]);
        $postTest = Activity::create(['course_id' => $course->id, 'type' => 'post-test', 'title' => 'Post-test pertama', 'sort_order' => 2]);
        $otherPostTest = Activity::create(['course_id' => $course->id, 'type' => 'post-test', 'title' => 'Post-test kedua', 'sort_order' => 3]);
        LearnItem::create(['learn_id' => $learn->id, 'activity_id' => $first->id, 'title' => $first->title, 'type' => 'literature', 'status' => 'done']);

        $question = Question::create(['activity_id' => $postTest->id, 'type' => 'post', 'description' => 'Tes', 'min_score' => 70, 'is_loop' => $repeatable]);
        $variant = QuestionVariant::create(['question_id' => $question->id, 'code' => 'A', 'variant' => 1, 'mix' => 0]);
        $item = QuestionItem::create(['question_variant_id' => $variant->id, 'description' => 'Soal', 'score' => 100]);
        QuestionOption::create(['question_item_id' => $item->id, 'option' => 'Benar', 'is_correct' => true]);
        QuestionOption::create(['question_item_id' => $item->id, 'option' => 'Salah', 'is_correct' => false]);

        return compact('participant', 'course', 'schedule', 'learn', 'first', 'postTest', 'otherPostTest', 'item');
    }

    private function submit(array $context, string $answer)
    {
        return $this->actingAs($context['participant'])->post(route('participant.learn.store', [
            $context['course']->slug, $context['schedule']->id, $context['postTest']->id,
        ]), [[
            'question_id' => $context['item']->id,
            'question' => 'Soal',
            'option_select' => $answer,
        ]]);
    }

    public function test_unchecked_first_post_test_resets_course_on_first_failure(): void
    {
        $context = $this->context(false);

        $this->submit($context, 'Salah')->assertRedirect(route('participant.learn.instruction.index', [
            $context['course']->slug, $context['schedule']->id, $context['first']->id,
        ]));

        $this->assertSoftDeleted($context['learn']);
        $this->assertSame(0, LearnItem::where('learn_id', $context['learn']->id)->count());
        $this->assertSame(2, Activity::where('course_id', $context['course']->id)->where('type', 'post-test')->count());
    }

    public function test_checked_post_test_resets_after_third_failed_submission(): void
    {
        $context = $this->context(true);

        foreach ([1, 2] as $attempt) {
            $this->submit($context, 'Salah')->assertRedirect(route('participant.learn.result.index', [
                $context['course']->slug, $context['schedule']->id, $context['postTest']->id,
            ]));
            $this->assertSame($attempt, (int) LearnItem::where('learn_id', $context['learn']->id)
                ->where('activity_id', $context['postTest']->id)->firstOrFail()->trial_total);
        }

        $this->submit($context, 'Salah')->assertRedirect(route('participant.learn.instruction.index', [
            $context['course']->slug, $context['schedule']->id, $context['first']->id,
        ]));
        $this->assertSoftDeleted($context['learn']);
        $this->assertSame(0, LearnItem::where('learn_id', $context['learn']->id)->count());
    }

    public function test_passing_post_test_does_not_reset_progress(): void
    {
        $context = $this->context(false);

        $this->submit($context, 'Benar')->assertRedirect(route('participant.learn.result.index', [
            $context['course']->slug, $context['schedule']->id, $context['postTest']->id,
        ]));

        $this->assertFalse($context['learn']->fresh()->trashed());
        $this->assertSame(2, LearnItem::where('learn_id', $context['learn']->id)->count());
    }
}
