<?php

namespace App\Http\Controllers\Admin\Course\Question;

use App\Models\Course\Course;
use App\Models\Course\Activity\Assessment\QuestionItem;
use App\Services\Course\Activity\Question\QuestionItemService;
use App\Http\Requests\Course\QuestionItemRequest;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class QuestionItemController
{
    protected $questionItem;

    public function __construct(QuestionItemService $questionItemService)
    {
        $this->questionItem = $questionItemService;
    }

    public function index(Course $course, $activityId, $variantId)
    {
        $this->assertVariant($course, $activityId, $variantId);
        $activity = $this->questionItem->getActivity($activityId, $variantId);
        if (request()->ajax() && !request()->header('X-Inertia')) {
            return DataTables::of($this->questionItem->getQuestionItems($variantId))->make();
        }
        return Inertia::render('Instructor/Course/Variant/QuestionItem/Index', [
            'slug' => $course->slug,
            'activityId' => $activityId,
            'questionId' => $variantId,
            'questionItems' => $this->questionItem->getQuestionItems($variantId),
            'activity' => [
                'type' => $activity->type == 'pre' ? 'Pre-Test' : 'Post-Test',
                'title' => $activity->title,
                'is_loop' => (bool) $activity->is_loop,
                'variant' => $activity->variant,
                'mix' => (bool) $activity->mix,
            ]
        ]);
    }

    public function create(Course $course, $activityId, $variantId)
    {
        $this->assertVariant($course, $activityId, $variantId);
        return Inertia::render('Instructor/Course/Variant/QuestionItem/Create', [
            'slug' => $course->slug,
            'activityId' => $activityId,
            'questionId' => $variantId,
        ]);
    }

    public function store(Course $course, $activityId, $variantId, QuestionItemRequest $request)
    {
        $this->assertVariant($course, $activityId, $variantId);
        try {
            $this->questionItem->createQuestionItem((string) $variantId, $request->validated());

            return to_route('admin.course.activity.variant.question-item.index', [$course->slug, $activityId, $variantId])->with('success', 'Soal berhasil ditambahkan.');
        } catch (\Throwable $th) {
            return to_route('admin.course.activity.variant.question-item.index', [$course->slug, $activityId, $variantId])->with('danger', 'Gagal menambahkan soal.');
        }
    }

    public function edit(Course $course, $activityId, $variantId, QuestionItem $question_item)
    {
        $this->assertVariant($course, $activityId, $variantId);
        abort_unless($question_item->question_variant_id === $variantId, 404);
        return Inertia::render('Instructor/Course/Variant/QuestionItem/Edit', [
            'question' => [
                'id' => $question_item->id,
                'description' => $question_item->description,
                'score' => $question_item->score,
                'option1' => $question_item->questionOptions[0]->option,
                'option2' => $question_item->questionOptions[1]->option,
                'option3' =>  $question_item->questionOptions[2]->option,
                'option4' =>  $question_item->questionOptions[3]->option,
                'correct_answer' => (string) ($question_item->questionOptions->search(fn ($option) => (bool) $option->is_correct) + 1)
            ],
            'slug' => $course->slug,
            'activityId' => $activityId,
            'questionId' => $variantId,
        ]);
    }

    public function update(Course $course, $activityId, $variantId, QuestionItem $question_item, QuestionItemRequest $request)
    {
        $this->assertVariant($course, $activityId, $variantId);
        abort_unless($question_item->question_variant_id === $variantId, 404);
        try {
            $this->questionItem->updateQuestionItem($question_item, $request->validated());

            return to_route('admin.course.activity.variant.question-item.index', [
                $course->slug,
                $activityId,
                $variantId
            ])->with('success', 'Soal berhasil diubah.');
        } catch (\Throwable $th) {
            return to_route('admin.course.activity.variant.question-item.index', [
                $course->slug,
                $activityId,
                $variantId
            ])->with('danger', 'Gagal mengubah soal.');
        }
    }

    public function destroy(Course $course, $activityId, $variantId, QuestionItem $question_item)
    {
        $this->assertVariant($course, $activityId, $variantId);
        abort_unless($question_item->question_variant_id === $variantId, 404);
        try {
            $this->questionItem->deleteQuestionItem($question_item);

            return to_route('admin.course.activity.variant.question-item.index', [
                $course->slug,
                $activityId,
                $variantId
            ])->with('success', 'Soal berhasil dihapus.');
        } catch (\Throwable $th) {
            return to_route('admin.course.activity.variant.question-item.index', [
                $course->slug,
                $activityId,
                $variantId
            ])->with('danger', 'Gagal menghapus soal.');
        }
    }
    private function assertVariant(Course $course, string $activityId, string $variantId): void
    {
        $this->questionItem->assertVariant((string) $course->id, $activityId, $variantId);
    }

}
