<?php

namespace App\Services\Course\Activity\Question;

use App\Models\Course\Question\QuestionItem;
use Illuminate\Support\Facades\DB;

class QuestionItemService
{
    public function getQuestionItems($questionId)
    {
        return QuestionItem::with('questionOptions')
            ->where('question_variant_id', $questionId)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'question' => $row->description,
                    'score' => $row->score,
                    'option1' => $row->questionOptions[0]->option,
                    'option2' => $row->questionOptions[1]->option,
                    'option3' =>  $row->questionOptions[2]->option,
                    'option4' =>  $row->questionOptions[3]->option,
                    'is_correct' => $row->questionOptions->where('is_correct', 1)->first()->option
                ];
            });
    }

    public function createQuestionItem() {}

    public function getActivity($activityId, $variant)
    {
        return DB::table('questions')
            ->select('questions.description as title', 'questions.type', 'questions.is_loop', 'question_variants.variant', 'question_variants.mix')
            ->join('question_variants', 'question_variants.question_id', '=', 'questions.id')
            ->where('questions.activity_id', $activityId)
            // ->where('questions.id', $questionId)
            ->where('question_variants.id', $variant)
            ->first();
    }
}
