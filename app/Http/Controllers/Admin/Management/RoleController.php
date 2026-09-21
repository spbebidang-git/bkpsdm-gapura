<?php

namespace App\Http\Controllers\Admin\Management;

use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class RoleController
{
    public function index()
    {
        if (request()->ajax() && ! request()->header('X-Inertia')) {
            return DataTables::of(Role::all())
                ->addIndexColumn()
                ->make();
        }

        return Inertia::render('Admin/Management/Role/Index');
    }
}
