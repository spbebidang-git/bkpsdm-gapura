<?php

namespace App\Repositories\Learning;

use App\Models\Learning\LearnItem;
use App\Repositories\EloquentRepository;
use App\Repositories\Learning\Contracts\LearnItemRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class LearnItemRepository extends EloquentRepository implements LearnItemRepositoryInterface
{
    public function __construct(LearnItem $model)
    {
        parent::__construct($model);
    }

    public function findByLearnAndActivity(int|string $learnId, int|string $activityId): ?LearnItem
    {
        return $this->query()->where('learn_id', $learnId)->where('activity_id', $activityId)->first();
    }

    public function getByLearn(int|string $learnId): Collection
    {
        return $this->query()->where('learn_id', $learnId)->orderBy('id')->get();
    }

    public function updateOrCreateForLearnAndActivity(
        int|string $learnId,
        int|string $activityId,
        array $attributes,
    ): LearnItem {
        return $this->query()->updateOrCreate(
            ['learn_id' => $learnId, 'activity_id' => $activityId],
            $attributes,
        );
    }
}
