<?php

namespace App\Repositories\Reporting\Contracts;

interface PlatformStatisticRepositoryInterface
{
    public function overview(): array;
    public function yearly(int $year): array;
    public function employeeStatus(): array;
}
