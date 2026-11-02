<?php

namespace App\Repositories\Course\Contracts;

use App\Models\Course\Category;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

interface CategoryRepositoryInterface extends BaseRepositoryInterface
{
    public function findByCode(string $code, bool $withTrashed = false): ?Category;

    public function restore(Category $category): Category;

    public function softDeleteMissingCodes(array $codes): int;

    public function adminTable(): Builder;
}
