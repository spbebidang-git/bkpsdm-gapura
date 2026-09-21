<?php

namespace App\Repositories\Course\Activity\Assessment;

use App\Models\Course\Activity\Assessment\Question;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionRepositoryInterface;
use App\Repositories\EloquentRepository;

class QuestionRepository extends EloquentRepository implements QuestionRepositoryInterface
{
    public function __construct(Question $model)
    {
        parent::__construct($model);
    }

    public function findByActivity(int|string $activityId, array $relations = []): ?Question
    {
        return $this->query()->with($relations)->where('activity_id', $activityId)->first();
    }

    public function findActivityVariant(int|string $activityId, int|string $variantId): ?object
    {
        return $this->query()
            ->select([
                'questions.description as title',
                'questions.type',
                'questions.is_loop',
                'question_variants.variant',
                'question_variants.mix',
            ])
            ->join('question_variants', 'question_variants.question_id', '=', 'questions.id')
            ->where('questions.activity_id', $activityId)
            ->where('question_variants.id', $variantId)
            ->first();
    }
}
