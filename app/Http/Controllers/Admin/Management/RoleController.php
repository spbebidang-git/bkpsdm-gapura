<?php

namespace App\Http\Controllers\Admin\Management;

use App\Services\System\RoleService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class RoleController
{
    public function __construct(
        private readonly RoleService $roles,
    ) {}

    public function index()
    {
        if (request()->ajax() && ! request()->header('X-Inertia')) {
            return DataTables::of($this->roles->getRolesForTable())
                ->addIndexColumn()
                ->make();
        }

        return Inertia::render('Admin/Management/Role/Index');
    }

    public function edit(Role $role)
    {
        abort_if($this->roles->isProtected($role), 403, 'Hak akses Super Admin selalu penuh.');

        return $this->form($role);
    }

    public function create()
    {
        return $this->form();
    }

    public function store(Request $request)
    {
        $validated = $this->validateRole($request);

        try {
            $this->roles->createRole($validated);
        } catch (InvalidArgumentException $e) {
            return back()->with('danger', $e->getMessage());
        }

        return to_route('admin.management.role.index')->with('success', 'Role baru berhasil dibuat.');
    }

    public function update(Request $request, Role $role)
    {
        $validated = $this->validateRole($request, $role);

        try {
            $this->roles->updateRole($role, $validated);
        } catch (InvalidArgumentException $e) {
            return back()->with('danger', $e->getMessage());
        }

        return to_route('admin.management.role.index')->with('success', 'Hak akses role berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        try {
            $this->roles->deleteRole($role);
        } catch (InvalidArgumentException $e) {
            return back()->with('danger', $e->getMessage());
        }

        return to_route('admin.management.role.index')->with('success', 'Role berhasil dihapus.');
    }

    private function form(?Role $role = null)
    {
        $data = $this->roles->getFormData($role);

        return Inertia::render('Admin/Management/Role/Form', $data);
    }

    private function validateRole(Request $request, ?Role $role = null): array
    {
        return $request->validate([
            'name' => [
                'required', 'string', 'max:50', 'alpha_dash',
                'unique:roles,name'.($role ? ','.$role->id : ''),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ], [], ['name' => 'Nama peran']);
    }
}
