<?php

namespace App\Services\System;

use App\Repositories\System\Contracts\RoleRepositoryInterface;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

class RoleService
{
    /** Role inti: nama & permission-nya tidak bebas diubah/dihapus. */
    public const CORE_ROLES = ['super_admin', 'administrator', 'trainer', 'participant'];

    public function __construct(
        private readonly RoleRepositoryInterface $roles,
    ) {}

    /** @return \Illuminate\Database\Eloquent\Collection<int, Role> */
    public function getRolesForTable()
    {
        return $this->roles->allRoles();
    }

    public function isCoreRole(?Role $role = null, ?string $name = null): bool
    {
        $name ??= $role?->name;

        return $name !== null && in_array($name, self::CORE_ROLES, true);
    }

    public function isProtected(?Role $role): bool
    {
        return $role?->name === 'super_admin';
    }

    /**
     * @param  array{name: string, permissions?: array<int, int|string>}  $data
     */
    public function createRole(array $data): Role
    {
        if ($this->isCoreRole(name: $data['name'])) {
            throw new InvalidArgumentException('Nama role inti tidak dapat dibuat ulang.');
        }

        return DB::transaction(function () use ($data) {
            $role = $this->roles->create([
                'name' => $data['name'],
                'guard_name' => 'web',
            ]);

            return $this->roles->syncPermissions($role, $data['permissions'] ?? []);
        });
    }

    /**
     * @param  array{name: string, permissions?: array<int, int|string>}  $data
     */
    public function updateRole(Role $role, array $data): Role
    {
        if ($this->isProtected($role)) {
            throw new InvalidArgumentException('Hak akses Super Admin selalu penuh.');
        }

        return DB::transaction(function () use ($role, $data) {
            if (! $this->isCoreRole($role)) {
                $role = $this->roles->update($role, ['name' => $data['name']]);
            }

            return $this->roles->syncPermissions($role, $data['permissions'] ?? []);
        });
    }

    public function deleteRole(Role $role): void
    {
        if ($this->isProtected($role)) {
            throw new InvalidArgumentException('Hak akses Super Admin selalu penuh.');
        }

        $this->roles->delete($role);
    }

    /**
     * Payload untuk form create/edit (Inertia).
     */
    public function getFormData(?Role $role = null): array
    {
        return [
            'role' => $role ? [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions()->pluck('id')->values()->all(),
                'is_core' => $this->isCoreRole($role),
            ] : null,
            'permissions' => $this->roles->assignablePermissions()->all(),
        ];
    }
}