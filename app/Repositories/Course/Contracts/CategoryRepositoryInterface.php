<?php

namespace App\Repositories\Course\Contracts;

use App\Models\Course\Category;
use App\Repositories\Contracts\BaseRepositoryInterface;

interface CategoryRepositoryInterface extends BaseRepositoryInterface
{
    public function findByCode(string $code, bool $withTrashed = false): ?Category;

    public function restore(Category $category): Category;

    public function softDeleteMissingCodes(array $codes): int;
}
