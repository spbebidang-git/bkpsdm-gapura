<?php

namespace App\Repositories\Support\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

interface HelpRepositoryInterface extends BaseRepositoryInterface
{
    public function getActive(): Collection;

    public function getLatestForAdmin(): Collection;
}
