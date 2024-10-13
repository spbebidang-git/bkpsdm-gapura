<?php

namespace App\Http\Controllers\Admin\Management;

use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class RoleController
{
    public function index()
    {
        if (request()->ajax()) {
            return DataTables::of(Role::all())
                ->addIndexColumn()
                ->make();
        }

        return view('admin.management.role.index');
    }
}
