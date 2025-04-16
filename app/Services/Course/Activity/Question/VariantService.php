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
                    'code' => $row->code,
                    'variant' => $row->variant,
                    'question_count' => $row->questionItems->count(),
                    'mix' => $row->mix ? 'Ya' : 'Tidak',
                    'loop' => $row->loop ? 'Ya' : 'Tidak'
                ];
            });
    }

    public function createVariant($activityId, $data)
    {
        QuestionVariant::create([
            'question_id' =>  $this->getQuestionId($activityId),
            'code' =>  $data['code'],
            'variant' =>  $data['variant'],
            'mix' =>  $data['mix'],
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

    public function generateVariantNumber($activityId)
    {
        $variant = QuestionVariant::where('question_id',  $this->getQuestionId($activityId))
            ->orderByDesc('id')
            ->first();
        if (!$variant) {
            return 1;
        }

        return $variant->variant + 1;
    }

    public function getQuestionId($activityId)
    {
        return Question::where('activity_id', $activityId)->first()->id;
    }
}
