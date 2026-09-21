<?php

namespace App\Repositories\Course\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface TrainerRepositoryInterface extends BaseRepositoryInterface
{
    public function getLatestForAdmin(): Collection;

    public function getAllOrderedByName(): Collection;
}
