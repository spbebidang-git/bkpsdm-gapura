<?php

namespace App\Repositories\Course\Activity\Assessment;

use App\Models\Course\Activity\Assessment\QuestionVariant;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionVariantRepositoryInterface;
use App\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Collection;

class QuestionVariantRepository extends EloquentRepository implements QuestionVariantRepositoryInterface
{
    public function __construct(QuestionVariant $model)
    {
        parent::__construct($model);
    }

    public function getByQuestion(int|string $questionId, array $relations = []): Collection
    {
        return $this->query()->with($relations)->where('question_id', $questionId)->orderBy('id')->get();
    }

    public function findByCode(int|string $questionId, string $code): ?QuestionVariant
    {
        return $this->query()->where('question_id', $questionId)->where('code', $code)->first();
    }

    public function getByQuestionWithItemCount(int|string $questionId): Collection
    {
        return $this->query()
            ->withCount('questionItems')
            ->where('question_id', $questionId)
            ->get();
    }

    public function findLatestByQuestion(int|string $questionId): ?QuestionVariant
    {
        return $this->query()->where('question_id', $questionId)->latest('id')->first();
    }

    public function findWithItems(int|string $id): QuestionVariant
    {
        return $this->query()->with('questionItems')->findOrFail($id);
    }
}
