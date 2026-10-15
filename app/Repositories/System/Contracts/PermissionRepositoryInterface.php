<?php

namespace App\Repositories\System\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;

interface PermissionRepositoryInterface extends BaseRepositoryInterface
{
    /** @return Collection<int, Permission> */
    public function allPermissions(): Collection;

    /** @return Collection<int, array{id: int|string, name: string}> */
    public function webGuardOrdered(): Collection;
}