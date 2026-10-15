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
            ->map(fn (QuestionItem $row) => $this->formatQuestionItem($row));
    }

    /**
     * Moodle questions may contain a variable number of answer choices.
     * Keeping the options as a collection prevents an incomplete imported
     * question from crashing the whole question-management page.
     */
    protected function formatQuestionItem(QuestionItem $row): array
    {
        $options = $row->questionOptions
            ->values()
            ->map(fn ($option) => [
                'id' => $option->id,
                'text' => $option->option,
                'is_correct' => (bool) $option->is_correct,
            ]);

        return [
            'id' => $row->id,
            'question' => $row->description,
            'score' => $row->score,
            'options' => $options,
        ];
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
