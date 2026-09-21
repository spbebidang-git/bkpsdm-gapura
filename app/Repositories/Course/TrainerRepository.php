<?php

namespace App\Repositories\Course;

use App\Models\Course\Trainer;
use App\Repositories\Course\Contracts\TrainerRepositoryInterface;
use App\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Collection;

class TrainerRepository extends EloquentRepository implements TrainerRepositoryInterface
{
    public function __construct(Trainer $model)
    {
        parent::__construct($model);
    }

    public function getLatestForAdmin(): Collection
    {
        return $this->query()->orderByDesc('id')->get();
    }

    public function getAllOrderedByName(): Collection
    {
        return $this->query()->orderBy('name')->get();
    }
}
