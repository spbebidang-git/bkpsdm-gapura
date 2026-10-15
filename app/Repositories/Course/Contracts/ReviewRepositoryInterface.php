<?php

namespace App\Repositories\Course\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface ReviewRepositoryInterface extends BaseRepositoryInterface
{
    public function getByCourse(int|string $courseId): Collection;

    public function getForAdmin(?int $courseId = null, ?int $star = null): Collection;
}
