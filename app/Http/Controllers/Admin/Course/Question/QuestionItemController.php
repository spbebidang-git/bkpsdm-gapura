<?php

namespace App\Http\Controllers\Admin\Course\Question;

use App\Models\Course\Course;
use App\Models\Course\Activity\Activity;
use App\Models\Course\Activity\Assessment\Question;
use App\Models\Course\Activity\Assessment\QuestionVariant;
use App\Models\Course\Activity\Assessment\QuestionItem;
use App\Models\Course\Activity\Assessment\QuestionOption;
use App\Services\Course\Activity\Question\QuestionItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;
use PhpParser\Node\Stmt\TryCatch;

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

    public function store(Course $course, $activityId, $variantId, Request $request)
    {
        $this->assertVariant($course, $activityId, $variantId);
        $request->validate([
            'description' => 'required|string',
            'options' => 'required|array:option1,option2,option3,option4',
            'options.option1' => 'required|string',
            'options.option2' => 'required|string',
            'options.option3' => 'required|string',
            'options.option4' => 'required|string',
            'correct_answer' => 'required|in:1,2,3,4',
            'score' => 'required|integer|min:1|max:100',
        ], [], ['description' => 'Pertanyaan']);

        try {
            DB::beginTransaction();

            $questionItem = QuestionItem::create([
                'question_variant_id' => $variantId,
                'description' => sanitize_plain_text($request->description),
                'score' => $request->score
            ]);

            $option = [];
            for ($i = 1; $i <= count($request->options); $i++) {
                $option[] = [
                    'question_item_id' => $questionItem->id,
                    'sort_order' => $i,
                    'option' => $request->options['option' . $i],
                    'is_correct' => $i == $request->correct_answer ? true : false
                ];
            }

            foreach ($option as $attributes) {
                QuestionOption::create($attributes);
            }

            DB::commit();

            return to_route('admin.course.activity.variant.question-item.index', [$course->slug, $activityId, $variantId])->with('success', 'Soal berhasil ditambahkan.');
        } catch (\Throwable $th) {
            DB::rollBack();

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

    public function update(Course $course, $activityId, $variantId, QuestionItem $question_item, Request $request)
    {
        $this->assertVariant($course, $activityId, $variantId);
        abort_unless($question_item->question_variant_id === $variantId, 404);
        $request->validate([
            'description' => 'required|string',
            'options' => 'required|array:option1,option2,option3,option4',
            'options.option1' => 'required|string',
            'options.option2' => 'required|string',
            'options.option3' => 'required|string',
            'options.option4' => 'required|string',
            'correct_answer' => 'required|in:1,2,3,4',
            'score' => 'required|integer|min:1|max:100',
        ], [], ['description' => 'Pertanyaan']);

        try {
            DB::beginTransaction();
            $question_item->update([
                'description' => sanitize_plain_text($request->description),
                'score' => $request->score
            ]);

            foreach ($question_item->questionOptions as $index => $existingOption) {
                $existingOption->update([
                    'option' => $request->options['option'.($index + 1)],
                    'sort_order' => $index + 1,
                    'is_correct' => (string) ($index + 1) === (string) $request->correct_answer,
                ]);
            }

            DB::commit();

            return to_route('admin.course.activity.variant.question-item.index', [
                $course->slug,
                $activityId,
                $variantId
            ])->with('success', 'Soal berhasil diubah.');
        } catch (\Throwable $th) {
            DB::rollBack();

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
            DB::beginTransaction();

            $question_item->delete();

            DB::commit();

            return to_route('admin.course.activity.variant.question-item.index', [
                $course->slug,
                $activityId,
                $variantId
            ])->with('success', 'Soal berhasil dihapus.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('admin.course.activity.variant.question-item.index', [
                $course->slug,
                $activityId,
                $variantId
            ])->with('danger', 'Gagal menghapus soal.');
        }
    }
    private function assertVariant(Course $course, string $activityId, string $variantId): void
    {
        abort_unless(Activity::whereKey($activityId)->where('course_id', $course->id)
            ->whereIn('type', ['pre-test', 'post-test'])->exists(), 404);
        $question = Question::where('activity_id', $activityId)->firstOrFail();
        abort_unless(QuestionVariant::whereKey($variantId)->where('question_id', $question->id)->exists(), 404);
    }

}
