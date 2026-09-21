<?php

namespace App\Repositories\Support;

use App\Models\Support\Help;
use App\Repositories\EloquentRepository;
use App\Repositories\Support\Contracts\HelpRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class HelpRepository extends EloquentRepository implements HelpRepositoryInterface
{
    public function __construct(Help $model)
    {
        parent::__construct($model);
    }

    public function getActive(): Collection
    {
        return $this->query()->where('is_active', true)->orderByDesc('id')->get();
    }

    public function getLatestForAdmin(): Collection
    {
        return $this->query()->orderByDesc('id')->get();
    }
}
