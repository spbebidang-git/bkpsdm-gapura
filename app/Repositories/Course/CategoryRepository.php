<?php

namespace App\Repositories\Course;

use App\Models\Course\Category;
use App\Repositories\Course\Contracts\CategoryRepositoryInterface;
use App\Repositories\EloquentRepository;

class CategoryRepository extends EloquentRepository implements CategoryRepositoryInterface
{
    public function __construct(Category $model)
    {
        parent::__construct($model);
    }

    public function findByCode(string $code, bool $withTrashed = false): ?Category
    {
        $query = $this->query();

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->where('code', $code)->first();
    }

    public function restore(Category $category): Category
    {
        $category->restore();

        return $category->refresh();
    }

    public function softDeleteMissingCodes(array $codes): int
    {
        return $this->query()
            ->whereNotNull('code')
            ->where('code', '!=', '')
            ->whereNotIn('code', $codes)
            ->delete();
    }
}
