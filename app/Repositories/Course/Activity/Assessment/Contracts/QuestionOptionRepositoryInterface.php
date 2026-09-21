<?php

namespace App\Repositories\Course\Activity\Assessment\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface QuestionOptionRepositoryInterface extends BaseRepositoryInterface
{
    public function getByQuestionItem(int|string $questionItemId): Collection;
}
