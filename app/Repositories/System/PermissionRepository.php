<?php

namespace App\Repositories\System;

use App\Repositories\EloquentRepository;
use App\Repositories\System\Contracts\PermissionRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Spatie\Permission\Models\Permission;

class PermissionRepository extends EloquentRepository implements PermissionRepositoryInterface
{
    public function __construct(Permission $model)
    {
        parent::__construct($model);
    }

    public function allPermissions(): Collection
    {
        return $this->query()->get();
    }

    public function webGuardOrdered(): Collection
    {
        return $this->query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->values();
    }
}