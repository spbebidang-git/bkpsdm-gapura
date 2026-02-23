<?php

namespace App\Http\Controllers\Instructor\Course\Question;

use App\Models\Course\Course;
use App\Models\Course\Question\QuestionItem;
use App\Models\Course\Question\QuestionOption;
use App\Services\Course\Activity\Question\QuestionItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
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
        $activity = $this->questionItem->getActivity($activityId, $variantId);
        // dd($activity);
        return Inertia::render('Instructor/Course/Variant/QuestionItem/Index', [
            'slug' => $course->slug,
            'activityId' => $activityId,
            'questionId' => $variantId,
            'questionItems' => $this->questionItem->getQuestionItems($variantId),
            'activity' => [
                'type' => $activity->type == 'pre' ? 'Pre-Test' : 'Post-Test',
                'title' => $activity->title,
                'is_loop' => $activity->is_loop ? 'Yes' : 'No',
                'variant' => $activity->variant,
                'mix' => $activity->mix ? 'Yes' : 'No',
            ]
        ]);
    }

    public function create(Course $course, $activityId, $variantId)
    {
        return Inertia::render('Instructor/Course/Variant/QuestionItem/Create', [
            'slug' => $course->slug,
            'activityId' => $activityId,
            'questionId' => $variantId,
        ]);
    }

    public function store(Course $course, $activityId, $variantId, Request $request)
    {
        $request->validate([
            'description' => 'required|string',
            'options' => 'required|array',
            'options.option1' => 'required|string',
            'options.option2' => 'required|string',
            'options.option3' => 'required|string',
            'options.option4' => 'required|string',
            'correct_answer' => 'required|string',
            'score' => 'required|integer|min:1|max:100',
        ]);

        try {
            DB::beginTransaction();

            $questionItem = QuestionItem::create([
                'question_variant_id' => $variantId,
                'description' => $request->description,
                'score' => $request->score
            ]);

            $option = [];
            for ($i = 1; $i <= count($request->options); $i++) {
                $option[] = [
                    'question_item_id' => $questionItem->id,
                    'option' => $request->options['option' . $i],
                    'is_correct' => $i == $request->correct_answer ? true : false
                ];
            }

            QuestionOption::insert($option);

            DB::commit();

            return to_route('instructor.course.activity.variant.question-item.index', [$course->slug, $activityId, $variantId])->with('success', 'Soal berhasil ditambahkan.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('instructor.course.activity.variant.question-item.index', [$course->slug, $activityId, $variantId])->with('danger', 'Gagal menambahkan soal.');
        }
    }

    public function edit(Course $course, $activityId, $variantId, QuestionItem $question_item)
    {
        return Inertia::render('Instructor/Course/Variant/QuestionItem/Edit', [
            'question' => [
                'id' => $question_item->id,
                'description' => $question_item->description,
                'score' => $question_item->score,
                'option1' => $question_item->questionOptions[0]->option,
                'option2' => $question_item->questionOptions[1]->option,
                'option3' =>  $question_item->questionOptions[2]->option,
                'option4' =>  $question_item->questionOptions[3]->option,
                'is_correct' => $question_item->questionOptions->where('is_correct', 1)->first()->option
            ],
            'slug' => $course->slug,
            'activityId' => $activityId,
            'questionId' => $variantId,
        ]);
    }

    public function update(Course $course, $activityId, $variantId, QuestionItem $question_item, Request $request)
    {
        $request->validate([
            'description' => 'required|string|max:255',
            'options' => 'required|array',
            'options.option1' => 'required|string|max:255',
            'options.option2' => 'required|string|max:255',
            'options.option3' => 'required|string|max:255',
            'options.option4' => 'required|string|max:255',
            'correct_answer' => 'required|string',
            'score' => 'required|integer|min:1|max:100',
        ]);

        try {
            DB::beginTransaction();
            $question_item->update([
                'description' => $request->description,
                'score' => $request->score
            ]);

            $option = [];
            for ($i = 1; $i <= count($request->options); $i++) {
                $option[] = [
                    'question_item_id' => $question_item->id,
                    'option' => $request->options['option' . $i],
                    'is_correct' => $request->options['option' . $i] == $request->correct_answer ? true : false
                ];
            }

            $existingOptions = QuestionOption::where('question_item_id', $question_item->id)->get();

            foreach ($existingOptions as $index => $existingOption) {
                $optionKey = 'option' . ($index + 1);

                $existingOption->update([
                    'option' => $request->options[$optionKey],
                    'is_correct' => $request->options[$optionKey] == $request->correct_answer,
                ]);
            }

            DB::commit();

            return to_route('instructor.course.activity.variant.question-item.index', [
                $course->slug,
                $activityId,
                $variantId
            ])->with('success', 'Soal berhasil diubah.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('instructor.course.activity.variant.question-item.index', [
                $course->slug,
                $activityId,
                $variantId
            ])->with('danger', 'Gagal mengubah soal.');
        }
    }

    public function destroy(Course $course, $activityId, $variantId, QuestionItem $question_item)
    {
        try {
            DB::beginTransaction();

            $question_item->delete();

            DB::commit();

            return to_route('instructor.course.activity.variant.question-item.index', [
                $course->slug,
                $activityId,
                $variantId
            ])->with('success', 'Soal berhasil dihapus.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('instructor.course.activity.variant.question-item.index', [
                $course->slug,
                $activityId,
                $variantId
            ])->with('danger', 'Gagal menghapus soal.');
        }
    }
}
