<?php

namespace App\Repositories\Course\Activity\Assessment;

use App\Models\Course\Activity\Assessment\QuestionItem;
use App\Repositories\Course\Activity\Assessment\Contracts\QuestionItemRepositoryInterface;
use App\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Collection;

class QuestionItemRepository extends EloquentRepository implements QuestionItemRepositoryInterface
{
    public function __construct(QuestionItem $model)
    {
        parent::__construct($model);
    }

    public function getByVariant(int|string $variantId, bool $withOptions = true): Collection
    {
        $query = $this->query()->where('question_variant_id', $variantId);

        if ($withOptions) {
            $query->with('questionOptions');
        }

        return $query->orderBy('id')->get();
    }

    public function findWithOptions(int|string $id): QuestionItem
    {
        return $this->query()->with('questionOptions')->findOrFail($id);
    }
}
