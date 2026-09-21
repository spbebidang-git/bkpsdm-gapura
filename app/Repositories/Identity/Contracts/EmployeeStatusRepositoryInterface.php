<?php

namespace App\Repositories\Identity\Contracts;

use App\Models\Identity\EmployeeStatus;
use App\Repositories\Contracts\BaseRepositoryInterface;

interface EmployeeStatusRepositoryInterface extends BaseRepositoryInterface
{
    public function findByTitle(string $title): ?EmployeeStatus;
}
