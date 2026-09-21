<?php

namespace App\Repositories\Course\Activity\Assessment\Contracts;

use App\Models\Course\Activity\Assessment\QuestionItem;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface QuestionItemRepositoryInterface extends BaseRepositoryInterface
{
    public function getByVariant(int|string $variantId, bool $withOptions = true): Collection;

    public function findWithOptions(int|string $id): QuestionItem;
}
