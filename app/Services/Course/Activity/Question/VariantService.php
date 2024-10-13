<?php

namespace App\Services\Course\Activity\Question;

use App\Models\Course\Question;
use App\Models\Course\Question\QuestionItem;
use App\Models\Course\Question\QuestionVariant;

class VariantService
{
    public function getVariant($activityId)
    {
        $question = Question::where('activity_id', $activityId)->first();

        if (!$question) {
            return false;
        }

        return QuestionVariant::where('question_id', $question->id)
            ->get()
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'variant' => $row->variant,
                    'question_count' => $row->questionItems->count(),
                    'mix' => $row->mix ? 'Ya' : 'Tidak',
                    'loop' => $row->loop ? 'Ya' : 'Tidak'
                ];
            });
    }

    public function createVariant($slug, $activityId, $data)
    {
        $question = Question::where('activity_id', $activityId)->first();

        QuestionVariant::create([
            'question_id' =>  $question->id,
            'variant' =>  $this->genereateVariantNumber($question->id),
            'mix' =>  $data['mix'],
            'loop' =>  $data['loop'],
        ]);
    }

    public function genereateVariantNumber($questionId)
    {
        $variant = QuestionVariant::where('question_id', $questionId)->first();

        if (!$variant) {
            return 1;
        }

        return $variant->variant + 1;
    }
}
