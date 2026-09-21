<?php

namespace App\Repositories\Course\Activity\Assessment\Contracts;

use App\Models\Course\Activity\Assessment\QuestionVariant;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface QuestionVariantRepositoryInterface extends BaseRepositoryInterface
{
    public function getByQuestion(int|string $questionId, array $relations = []): Collection;

    public function findByCode(int|string $questionId, string $code): ?QuestionVariant;

    public function getByQuestionWithItemCount(int|string $questionId): Collection;

    public function findLatestByQuestion(int|string $questionId): ?QuestionVariant;

    public function findWithItems(int|string $id): QuestionVariant;
}
