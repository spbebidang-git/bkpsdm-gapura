<?php

namespace App\Repositories\Identity;

use App\Models\Identity\EmployeeStatus;
use App\Repositories\EloquentRepository;
use App\Repositories\Identity\Contracts\EmployeeStatusRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class EmployeeStatusRepository extends EloquentRepository implements EmployeeStatusRepositoryInterface
{
    public function __construct(EmployeeStatus $model)
    {
        parent::__construct($model);
    }

    public function tableQuery(): Builder
    {
        return $this->query()->orderBy('title');
    }

    public function findByTitle(string $title): ?EmployeeStatus
    {
        return $this->query()->where('title', $title)->first();
    }

    public function getAllOrderedByTitle(): Collection
    {
        return $this->query()->orderBy('title')->get();
    }
}
