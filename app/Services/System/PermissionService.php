<?php

namespace App\Services\System;

use App\Repositories\System\Contracts\PermissionRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class PermissionService
{
    public function __construct(
        private readonly PermissionRepositoryInterface $permissions,
    ) {}

    /** @return \Illuminate\Database\Eloquent\Collection<int, Permission> */
    public function getPermissionsForTable()
    {
        return $this->permissions->allPermissions();
    }

    /**
     * @param  array{name: string}  $data
     */
    public function createPermission(array $data): Permission
    {
        return DB::transaction(fn () => $this->permissions->create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]));
    }

    /**
     * @param  array{name: string}  $data
     */
    public function updatePermission(Permission $permission, array $data): Permission
    {
        return DB::transaction(fn () => $this->permissions->update($permission, [
            'name' => $data['name'],
        ]));
    }

    public function deletePermission(Permission $permission): void
    {
        DB::transaction(fn () => $this->permissions->delete($permission));
    }
}