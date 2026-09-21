<?php

namespace App\Http\Controllers\Admin\Management;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class PermissionController
{
    public function index()
    {
        if (request()->ajax() && ! request()->header('X-Inertia')) {
            return DataTables::of(Permission::all())
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
        $request->validate(['name' => 'required|string']);

        try {
            DB::beginTransaction();
            Permission::create([
                'name' => $request->name,
                'guard_name' => 'web',
            ]);
            DB::commit();

            return to_route('admin.management.permission.index')->with('success', 'Permission has been saved.');
        } catch (\Throwable) {
            DB::rollBack();

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
        $request->validate(['name' => 'required|string']);

        try {
            DB::beginTransaction();
            $permission->update($request->only('name'));
            DB::commit();

            return to_route('admin.management.permission.index')->with('success', 'Permission has been updated.');
        } catch (\Throwable) {
            DB::rollBack();

            return to_route('admin.management.permission.index')->with('danger', 'Something went wrong.');
        }
    }

    public function destroy(Permission $permission)
    {
        try {
            DB::beginTransaction();
            $permission->delete();
            DB::commit();

            return to_route('admin.management.permission.index')->with('success', 'Permission has been deleted.');
        } catch (\Throwable) {
            DB::rollBack();

            return to_route('admin.management.permission.index')->with('danger', 'Something went wrong.');
        }
    }
}
