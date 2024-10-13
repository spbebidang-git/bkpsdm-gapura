<?php

namespace App\Http\Controllers\Instructor\Course\Question;

use App\Models\Course\Question\QuestionItem;
use App\Models\Course\Question\QuestionOption;
use App\Services\Course\Activity\Question\QuestionItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class QuestionItemController
{
    protected $questionItem;

    public function __construct(QuestionItemService $questionItemService)
    {
        $this->questionItem = $questionItemService;
    }


    public function index($slug, $activityId, $questionId)
    {
        return Inertia::render('Instructor/Course/Variant/QuestionItem/Index', [
            'slug' => $slug,
            'activityId' => $activityId,
            'questionId' => $questionId,
            'questionItems' => $this->questionItem->getQuestionItems($questionId)
        ]);
    }

    public function create($slug, $activityId, $questionId)
    {
        return Inertia::render('Instructor/Course/Variant/QuestionItem/Create', [
            'slug' => $slug,
            'activityId' => $activityId,
            'questionId' => $questionId,
        ]);
    }

    public function store($slug, $activityId, $questionId, Request $request)
    {
        // $request->validate([
        //     'description' => 'required',
        //     'options.*' => 'required',
        //     'score' => 'required',
        //     'correct_answer' => 'required'
        // ]);

        // dd($request->options);

        try {
            DB::beginTransaction();

            $questionItem = QuestionItem::create([
                'question_variant_id' => $questionId,
                'description' => $request->description,
                'score' => $request->score
            ]);

            $option = [];
            // dd($request->options['option1']);
            for ($i = 1; $i <= count($request->options); $i++) {

                $option[] = [
                    'question_item_id' => $questionItem->id,
                    'option' => $request->options['option' . $i],
                    'is_correct' => $i + 1 == $request->correct_answer ? true : false
                ];
            }

            QuestionOption::insert($option);

            DB::commit();

            return to_route('instructor.course.activity.variant.question-item.index', [$slug, $activityId, $questionId]);
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);

            return to_route('instructor.course.activity.variant.question-item.index', [$slug, $activityId, $questionId]);
        }
    }
}
