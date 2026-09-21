<?php

namespace App\Repositories\Identity;

use App\Models\Identity\EmployeeStatus;
use App\Repositories\EloquentRepository;
use App\Repositories\Identity\Contracts\EmployeeStatusRepositoryInterface;

class EmployeeStatusRepository extends EloquentRepository implements EmployeeStatusRepositoryInterface
{
    public function __construct(EmployeeStatus $model)
    {
        parent::__construct($model);
    }

    public function findByTitle(string $title): ?EmployeeStatus
    {
        return $this->query()->where('title', $title)->first();
    }
}
