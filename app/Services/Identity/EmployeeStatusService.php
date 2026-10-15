<?php

namespace App\Services\Identity;

use App\Models\Identity\EmployeeStatus;
use App\Repositories\Identity\Contracts\EmployeeStatusRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class EmployeeStatusService
{
    public function __construct(
        private readonly EmployeeStatusRepositoryInterface $statuses,
    ) {}

    /** @return Builder<EmployeeStatus> */
    public function getForTable(): Builder
    {
        return $this->statuses->tableQuery();
    }

    public function createStatus(array $data): EmployeeStatus
    {
        return $this->statuses->create($data);
    }

    public function updateStatus(EmployeeStatus $status, array $data): EmployeeStatus
    {
        return $this->statuses->update($status, $data);
    }

    public function deleteStatus(EmployeeStatus $status): void
    {
        $this->statuses->delete($status);
    }
}
