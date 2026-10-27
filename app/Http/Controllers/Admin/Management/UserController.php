<?php

namespace App\Http\Controllers\Admin\Management;

use App\Http\Controllers\Controller;
use App\Models\Identity\User;
use App\Http\Requests\Management\UserRequest;
use App\Services\Identity\AdminUserService;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function __construct(private readonly AdminUserService $users) {}

    public function index()
    {
        $this->ensureSuperAdmin();

        if (request()->ajax() && ! request()->header('X-Inertia')) {
            return DataTables::of(
                $this->users->managementUsers()
            )
                ->addIndexColumn()
                ->addColumn('roles', fn (User $user) => $user->getRoleNames()->values()->all())
                ->addColumn('auth_source_label', fn (User $user) => $user->usesPrestigeAuthentication() ? 'Prestige' : ($user->usesLocalAuthentication() ? 'GAPURA lokal' : 'Kedaton SSO'))
                ->make();
        }

        return Inertia::render('Admin/Management/User/Index');
    }

    public function create()
    {
        $this->ensureSuperAdmin();

        return $this->form();
    }

    public function store(UserRequest $request)
    {
        $this->ensureSuperAdmin();

        $this->users->create($request->validated());

        return to_route('admin.management.user.index')->with('success', 'Pengguna baru berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $this->ensureSuperAdmin();
        abort_unless($user->hasRole('administrator') || $user->hasRole('super_admin'), 404);
        abort_if($user->hasRole('super_admin'), 403, 'Role Super Admin tidak dapat diubah.');

        return $this->form($user);
    }

    public function update(UserRequest $request, User $user)
    {
        $this->ensureSuperAdmin();
        abort_unless($user->hasRole('administrator') || $user->hasRole('super_admin'), 404);
        abort_if($user->hasRole('super_admin'), 403, 'Role Super Admin tidak dapat diubah.');

        $this->users->update($user, $request->validated());

        return to_route('admin.management.user.index')->with('success', 'User has been updated.');
    }

    public function destroy(User $user)
    {
        $this->ensureSuperAdmin();
        abort_unless($user->hasRole('administrator') || $user->hasRole('super_admin'), 404);
        abort_if($user->hasRole('super_admin'), 403, 'Pengguna Super Admin tidak dapat dihapus.');

        $this->users->delete($user);

        return to_route('admin.management.user.index')->with('success', 'Pengguna berhasil dihapus.');
    }

    private function form(?User $user = null)
    {
        return Inertia::render('Admin/Management/User/Form', [
            'user' => $user ? [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'nip' => $user->nip,
                'auth_source' => $user->auth_source,
                'opd_id' => $user->opd_id,
                'roles' => $user->getRoleNames()->values()->all(),
            ] : null,
            'opds' => $this->users->activeOpds(),
            'available_roles' => $this->users->availableRoles(),
            // Compatibility with the already-loaded development module.
            'roles' => $this->users->availableRoles(),
        ]);
    }

    private function ensureSuperAdmin(): void
    {
        abort_unless(request()->user()?->hasRole('super_admin'), 403);
    }

}
