<?php

namespace App\Repositories\Course\Activity\Assessment;

use App\Models\Course\Activity\Assessment\QuestionOption;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionOptionRepositoryInterface;
use App\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Collection;

class QuestionOptionRepository extends EloquentRepository implements QuestionOptionRepositoryInterface
{
    public function __construct(QuestionOption $model)
    {
        parent::__construct($model);
    }

    public function getByQuestionItem(int|string $questionItemId): Collection
    {
        return $this->query()->where('question_item_id', $questionItemId)->orderBy('id')->get();
    }
}
