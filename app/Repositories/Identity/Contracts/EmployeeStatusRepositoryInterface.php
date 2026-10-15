<?php

namespace App\Repositories\Identity\Contracts;

use App\Models\Identity\EmployeeStatus;
use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

interface EmployeeStatusRepositoryInterface extends BaseRepositoryInterface
{
    /** @return Builder<EmployeeStatus> */
    public function tableQuery(): Builder;

    public function findByTitle(string $title): ?EmployeeStatus;

    public function getAllOrderedByTitle(): Collection;
}
