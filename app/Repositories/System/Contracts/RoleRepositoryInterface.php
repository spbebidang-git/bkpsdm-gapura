<?php

namespace App\Repositories\System\Contracts;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Role;

interface RoleRepositoryInterface extends BaseRepositoryInterface
{
    /** @return Collection<int, Role> */
    public function allRoles(): Collection;

    /** @return Collection<int, array{id: int|string, name: string}> */
    public function assignablePermissions(): Collection;

    public function syncPermissions(Role $role, array $permissionIds): Role;
}