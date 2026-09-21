<?php

namespace App\Services\Course\Activity\Question;

use App\Repositories\Course\Activity\Assessment\Contracts\QuestionItemRepositoryInterface;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionRepositoryInterface;

class QuestionItemService
{
    public function __construct(
        private readonly QuestionItemRepositoryInterface $questionItems,
        private readonly QuestionRepositoryInterface $questions,
    ) {}

    public function getQuestionItems($questionId)
    {
        return $this->questionItems->getByVariant($questionId)
            ->map(function ($row) {
                return [
                    'id' => $row->id,
                    'question' => $row->description,
                    'score' => $row->score,
                    'option1' => $row->questionOptions[0]->option,
                    'option2' => $row->questionOptions[1]->option,
                    'option3' => $row->questionOptions[2]->option,
                    'option4' => $row->questionOptions[3]->option,
                    'is_correct' => $row->questionOptions->where('is_correct', 1)->first()->option,
                ];
            });
    }

    public function createQuestionItem() {}

    public function getActivity($activityId, $variant)
    {
        return $this->questions->findActivityVariant($activityId, $variant);
    }
}
