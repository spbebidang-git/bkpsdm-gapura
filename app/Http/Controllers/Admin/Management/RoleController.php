<?php

namespace App\Http\Controllers\Admin\Management;

use App\Http\Requests\Admin\RoleRequest;
use App\Services\System\RoleService;
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

    public function store(RoleRequest $request)
    {
        $validated = $request->validated();

        try {
            $this->roles->createRole($validated);
        } catch (InvalidArgumentException $e) {
            return back()->with('danger', $e->getMessage());
        }

        return to_route('admin.management.role.index')->with('success', 'Role baru berhasil dibuat.');
    }

    public function update(RoleRequest $request, Role $role)
    {
        $validated = $request->validated();

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
}
