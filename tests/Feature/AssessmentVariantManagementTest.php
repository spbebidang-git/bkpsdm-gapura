<?php

namespace Tests\Feature;

use App\Http\Middleware\IsAdminMiddleware;
use App\Models\Course\Activity\Activity;
use App\Models\Course\Activity\Assessment\Question;
use App\Models\Course\Activity\Assessment\QuestionVariant;
use App\Models\Course\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AssessmentVariantManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_settings_are_recovered_and_variants_can_be_configured_for_both_tests(): void
    {
        $this->withoutMiddleware(IsAdminMiddleware::class);
        $course = Course::factory()->draft()->create(['is_locked' => false]);
        foreach (['pre-test' => 'pre', 'post-test' => 'post'] as $type => $questionType) {
            $activity = Activity::create([
                'course_id' => $course->id, 'type' => $type, 'title' => $type,
                'timer' => 0, 'value' => 0,
            ]);
            $migration = require database_path('migrations/2026_10_13_000001_backfill_missing_assessment_questions.php');
            $migration->up();
            $question = Question::where('activity_id', $activity->id)->firstOrFail();
            $this->assertSame($questionType, $question->type);
            $question->update(['description' => 'Existing settings', 'min_score' => 75]);
            $migration->up();
            $this->assertSame(1, Question::where('activity_id', $activity->id)->count());
            $this->assertSame(75, $question->fresh()->min_score);

            $params = [$course->slug, $activity->id];
            $index = route('admin.course.activity.variant.index', $params);
            $this->get($index)->assertOk()->assertInertia(fn (Assert $page) => $page
                ->component('Instructor/Course/Variant/Index')->has('variants', 0));
            $this->get(route('admin.course.activity.variant.create', $params))->assertOk();
            $this->post(route('admin.course.activity.variant.store', $params), [
                'code' => $type.'-A', 'variant' => 1, 'mix' => 0,
            ])->assertRedirect($index)->assertSessionHasNoErrors();
            $variant = QuestionVariant::where('question_id', $question->id)->firstOrFail();
            $this->get($index)->assertInertia(fn (Assert $page) => $page->where('variants.0.mix', false));
            $this->put(route('admin.course.activity.variant.update', [...$params, $variant->id]), [
                'code' => $type.'-B', 'variant' => 1, 'mix' => '1',
            ])->assertRedirect($index)->assertSessionHasNoErrors();
            $this->assertDatabaseHas('question_variants', ['id' => $variant->id, 'mix' => 1, 'code' => $type.'-B']);
            $this->get($index)->assertInertia(fn (Assert $page) => $page->where('variants.0.mix', true));
            $this->putJson(route('admin.course.activity.variant.update', [...$params, $variant->id]), [
                'code' => '', 'variant' => 1, 'mix' => '',
            ])->assertUnprocessable()->assertJsonValidationErrors(['code', 'mix']);
        }
    }
    public function test_question_crud_keeps_the_correct_choice_when_its_text_changes(): void
    {
        $this->withoutMiddleware(IsAdminMiddleware::class);
        [$course, $activity, $question] = $this->assessment();
        $variant = QuestionVariant::create(['question_id' => $question->id, 'code' => 'A', 'variant' => 1, 'mix' => 0]);
        $params = [$course->slug, $activity->id, $variant->id];
        $payload = ['description' => 'Pertanyaan', 'score' => 25, 'correct_answer' => '2',
            'options' => ['option1' => 'A', 'option2' => 'B', 'option3' => 'C', 'option4' => 'D']];
        $index = route('admin.course.activity.variant.question-item.index', $params);
        $this->post(route('admin.course.activity.variant.question-item.store', $params), $payload)->assertRedirect($index)->assertSessionHas('success');
        $item = $variant->questionItems()->firstOrFail();
        $this->assertSame(4, $item->questionOptions()->count());
        $this->assertSame('B', $item->questionOptions()->where('is_correct', true)->first()->option);
        $this->get(route('admin.course.activity.variant.question-item.edit', [...$params, $item->id]))
            ->assertInertia(fn (Assert $page) => $page->where('question.correct_answer', '2'));
        $payload['options']['option2'] = 'Jawaban diubah';
        $this->put(route('admin.course.activity.variant.question-item.update', [...$params, $item->id]), $payload)->assertRedirect($index)->assertSessionHas('success');
        $this->assertSame('Jawaban diubah', $item->questionOptions()->where('is_correct', true)->first()->option);
        $this->getJson($index, ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonPath('recordsTotal', 1)->assertJsonPath('data.0.correct_answer', '2');
        $this->getJson(route('admin.course.activity.variant.index', [$course->slug, $activity->id]), ['X-Requested-With' => 'XMLHttpRequest'])->assertOk()->assertJsonPath('recordsTotal', 1);
        $this->delete(route('admin.course.activity.variant.question-item.destroy', [...$params, $item->id]))->assertRedirect($index);
        $this->assertSoftDeleted($item);
        $this->delete(route('admin.course.activity.variant.destroy', $params))->assertRedirect();
        $this->assertSoftDeleted($variant);
    }

    public function test_excel_import_uses_ulids_and_is_limited_to_once(): void
    {
        $this->withoutMiddleware(IsAdminMiddleware::class);
        [$course, $activity, $question] = $this->assessment();
        $sheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet->getActiveSheet()->setTitle('Variants')->fromArray([
            ['code', 'variant', 'mix'], ['A', 1, 0],
        ]);
        $details = $sheet->createSheet()->setTitle('A');
        $details->fromArray([
            ['description', 'score', 'option_a', 'option_b', 'option_c', 'option_d', 'correct'],
            ['Soal impor', 25, 'Satu', 'Dua', 'Tiga', 'Empat', 'Dua'],
        ]);
        $path = tempnam(sys_get_temp_dir(), 'assessment-');
        try {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($sheet))->save($path);
            $file = new \Illuminate\Http\UploadedFile($path, 'questions.xlsx', null, null, true);
            $url = route('admin.course.activity.import-question.store', [$course->slug, $activity->id]);
            $details->setCellValue('G2', 'Kunci tidak ada');
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($sheet))->save($path);
            $this->post($url, ['content' => $file])->assertSessionHasErrors('content');
            $this->assertSame(0, $question->questionVariant()->count());
            $this->assertFalse((bool) $question->fresh()->has_imported);
            $details->setCellValue('G2', 'Dua');
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($sheet))->save($path);
            $this->post($url, ['content' => $file])->assertRedirect(route('admin.course.activity.variant.index', [$course->slug, $activity->id]))->assertSessionHas('success');
            $variant = $question->questionVariant()->firstOrFail();
            $item = $variant->questionItems()->firstOrFail();
            $this->assertSame(4, $item->questionOptions()->count());
            $this->assertSame('Dua', $item->questionOptions()->where('is_correct', true)->first()->option);
            $this->post($url, ['content' => $file])->assertSessionHasErrors('content');
            $this->assertSame(1, $question->questionVariant()->count());
        } finally {
            @unlink($path);
        }
    }

    private function assessment(): array
    {
        $course = Course::factory()->draft()->create(['is_locked' => false]);
        $activity = Activity::create(['course_id' => $course->id, 'type' => 'pre-test', 'title' => 'Pre-Test', 'timer' => 0, 'value' => 0]);
        $question = Question::create(['activity_id' => $activity->id, 'type' => 'pre', 'description' => 'Tes', 'min_score' => 0, 'is_loop' => 0]);
        return [$course, $activity, $question];
    }

}
