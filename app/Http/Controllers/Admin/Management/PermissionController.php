<?php

namespace App\Http\Controllers\Admin\Management;


use Illuminate\Http\Request;
use App\Imports\PermissionImport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Permission;
use Yajra\DataTables\Facades\DataTables;

class PermissionController
{
    public function index()
    {
        if (request()->ajax()) {
            return DataTables::of(Permission::all())
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = '<div class="d-flex justify-content-center gap-2">';
                    $btn .= '<a class="btn btn-sm btn-warning" href=' . route('admin.management.permission.edit', $row->id) . '>';
                    $btn .= '<i class="bx bx-edit"></i></a>';
                    $btn .= '<form action="' . route('admin.management.permission.destroy', $row->id) . '" method="POST" onsubmit="return confirmDelete(event)">';
                    $btn .= '<input type="hidden" name="_token" value="' . csrf_token() . '">';
                    $btn .= '<input type="hidden" name="_method" value="DELETE">';
                    $btn .= '<button class="btn btn-sm btn-danger">';
                    $btn .= '<i class="bx bx-trash"></i></button>';
                    $btn .= '</form></div>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make();
        }

        return view('admin.management.permission.index');
    }

    public function create()
    {
        return view('admin.management.permission.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string'
        ]);

        try {
            DB::beginTransaction();

            $request->request->add(['guard_name' => 'web']);

            Permission::create($request->all());

            DB::commit();

            return to_route('admin.management.permission.index')->with('success', 'Permission has been saved.');
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);
            return to_route('admin.management.permission.index')->with('danger', 'Something went wrong.');
        }
    }

    public function edit(Permission $permission)
    {
        return view('admin.management.permission.edit', compact('permission'));
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|string'
        ]);

        try {
            DB::beginTransaction();

            $permission->update($request->only('name'));

            DB::commit();

            return to_route('admin.management.permission.index')->with('success', 'Permission has been updated.');
        } catch (\Throwable $th) {
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
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('admin.management.permission.index')->with('danger', 'Something went wrong.');
        }
    }
}
