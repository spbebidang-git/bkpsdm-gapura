<?php

namespace App\Http\Controllers\Admin\Management;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            return DataTables::of(
                User::with("roles")->whereHas("roles", function ($q) {
                    $q->whereIn("name", ['Admin']);
                })->orderBy('id')->get()
            )
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = '<div class="d-flex justify-content-center gap-2">';
                    $btn .= '<a class="btn btn-sm btn-warning" href=' . route('admin.management.user.edit', $row->id) . '>';
                    $btn .= '<i class="bx bx-edit"></i></a>';
                    $btn .= '<form action="' . route('admin.management.user.destroy', $row->id) . '" method="POST" class="form-delete">';
                    $btn .= '<input type="hidden" name="_token" value="' . csrf_token() . '">';
                    $btn .= '<input type="hidden" name="_method" value="DELETE">';
                    $btn .= '<button class="btn btn-sm btn-danger">';
                    $btn .= '<i class="bx bx-trash"></i></button>';
                    $btn .= '</form></div>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->addColumn('role', function ($row) {
                    return $row->roles()->pluck('name');
                })
                ->make();
        }

        return view('admin.management.user.index');
    }

    public function create()
    {
        $roles = Role::whereName('Admin')->get();

        return view('admin.management.user.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($request->role);

        return to_route('admin.management.user.index')->with('success', 'User has been saved.');
    }

    public function edit(User $user)
    {
        $roles = Role::whereName('Admin')->get();

        return view('admin.management.user.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email, ' . $user->id . '',
            'password' => 'nullable|string'
        ]);

        $user->update($request->only('name', 'email'));

        if ($request->password) {
            $user->password = Hash::make($request->password);
        }

        return to_route('admin.management.user.index')->with('success', 'User has been updated.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return to_route('admin.management.user.index')->with('success', 'User has been deleted.');
    }
}
