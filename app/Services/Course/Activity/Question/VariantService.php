<?php

namespace App\Services\Course\Activity\Question;

use App\Repositories\Course\Activity\Assessment\Contracts\QuestionRepositoryInterface;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionVariantRepositoryInterface;

class VariantService
{
    public function __construct(
        private readonly QuestionRepositoryInterface $questions,
        private readonly QuestionVariantRepositoryInterface $variants,
    ) {}

    public function getVariant($activityId)
    {
        $question = $this->questions->findByActivity($activityId);

        if (! $question) {
            return false;
        }

        return $this->variants->getByQuestion($question->id, ['questionItems'])
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'updated_at' => $row->updated_at,
                    'code' => $row->code,
                    'variant' => $row->variant,
                    'question_count' => $row->questionItems->count(),
                    'mix' => (bool) $row->mix,
                    'loop' => $row->loop ? 'Ya' : 'Tidak',
                ];
            });
    }

    public function createVariant($activityId, $data)
    {
        $this->variants->create([
            'question_id' => $this->getQuestionId($activityId),
            'code' => $data['code'],
            'variant' => $data['variant'],
            'mix' => $data['mix'],
        ]);
    }

    public function genereateVariantNumber($questionId)
    {
        $variant = $this->variants->getByQuestion($questionId)->first();

        if (! $variant) {
            return 1;
        }

        return $variant->variant + 1;
    }

    public function generateVariantNumber($activityId)
    {
        $variant = $this->variants->findLatestByQuestion($this->getQuestionId($activityId));
        if (! $variant) {
            return 1;
        }

        return $variant->variant + 1;
    }

    public function getQuestionId($activityId)
    {
        return $this->questions->findByActivity($activityId)?->id;
    }

    public function getVariantWarning($activityId)
    {
        $question = $this->questions->findByActivity($activityId);
        if (! $question) {
            return null;
        }

        $variants = $this->variants->getByQuestionWithItemCount($question->id);

        if ($variants->isEmpty()) {
            return null;
        }

        $counts = $variants->pluck('question_items_count');
        $hasEmpty = $counts->contains(0);
        $hasMismatch = $counts->unique()->count() > 1;

        if (! $hasEmpty && ! $hasMismatch) {
            return null;
        }

        return [
            'hasEmpty' => $hasEmpty,
            'hasMismatch' => $hasMismatch,
            'emptyVariants' => $variants->where('question_items_count', 0)
                ->pluck('variant')->toArray(),
        ];
    }
}
