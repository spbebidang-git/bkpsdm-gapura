<?php

namespace App\Http\Controllers\Admin\Management;

use App\Http\Controllers\Controller;
use App\Models\Identity\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index()
    {
        if (request()->ajax() && ! request()->header('X-Inertia')) {
            return DataTables::of(
                User::with('roles')->whereHas('roles', fn ($q) => $q->whereIn('name', ['Admin']))->orderBy('id')->get()
            )
                ->addIndexColumn()
                ->addColumn('role', fn ($row) => $row->roles->pluck('name')->values())
                ->make();
        }

        return Inertia::render('Admin/Management/User/Index');
    }

    public function create()
    {
        return Inertia::render('Admin/Management/User/Form', [
            'user' => null,
            'roles' => Role::whereName('Admin')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|string',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
        ]);
        $user->assignRole($request->role);

        return to_route('admin.management.user.index')->with('success', 'User has been saved.');
    }

    public function edit(User $user)
    {
        return Inertia::render('Admin/Management/User/Form', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->getRoleNames()->first(),
            ],
            'roles' => Role::whereName('Admin')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|string|unique:users,email,'.$user->id,
        ]);

        $user->update($request->only('name', 'email'));

        return to_route('admin.management.user.index')->with('success', 'User has been updated.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return to_route('admin.management.user.index')->with('success', 'User has been deleted.');
    }
}
