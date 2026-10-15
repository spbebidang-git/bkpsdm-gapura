<?php

namespace App\Http\Controllers\Admin\Management;

use App\Services\System\PermissionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Throwable;
use Yajra\DataTables\Facades\DataTables;

class PermissionController
{
    public function __construct(
        private readonly PermissionService $permissions,
    ) {}

    public function index()
    {
        if (request()->ajax() && ! request()->header('X-Inertia')) {
            return DataTables::of($this->permissions->getPermissionsForTable())
                ->addIndexColumn()
                ->make();
        }

        return Inertia::render('Admin/Management/Permission/Index');
    }

    public function create()
    {
        return Inertia::render('Admin/Management/Permission/Form', ['permission' => null]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string'], [], ['name' => 'Nama hak akses']);

        try {
            $this->permissions->createPermission($validated);

            return to_route('admin.management.permission.index')->with('success', 'Permission has been saved.');
        } catch (Throwable) {
            return to_route('admin.management.permission.index')->with('danger', 'Something went wrong.');
        }
    }

    public function edit(Permission $permission)
    {
        return Inertia::render('Admin/Management/Permission/Form', [
            'permission' => [
                'id' => $permission->id,
                'name' => $permission->name,
            ],
        ]);
    }

    public function update(Request $request, Permission $permission)
    {
        $validated = $request->validate(['name' => 'required|string'], [], ['name' => 'Nama hak akses']);

        try {
            $this->permissions->updatePermission($permission, $validated);

            return to_route('admin.management.permission.index')->with('success', 'Permission has been updated.');
        } catch (Throwable) {
            return to_route('admin.management.permission.index')->with('danger', 'Something went wrong.');
        }
    }

    public function destroy(Permission $permission)
    {
        try {
            $this->permissions->deletePermission($permission);

            return to_route('admin.management.permission.index')->with('success', 'Permission has been deleted.');
        } catch (Throwable) {
            return to_route('admin.management.permission.index')->with('danger', 'Something went wrong.');
        }
    }
}
