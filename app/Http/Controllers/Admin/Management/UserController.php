<?php

namespace App\Http\Controllers\Admin\Management;

use App\Http\Controllers\Controller;
use App\Models\Identity\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index()
    {
        $this->ensureSuperAdmin();

        if (request()->ajax() && ! request()->header('X-Inertia')) {
            return DataTables::of(
                User::role(['super_admin', 'administrator'])->with('roles')->orderBy('id')->get()
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

    public function store(Request $request)
    {
        $this->ensureSuperAdmin();

        $validated = $this->validateUser($request);

        $user = User::create([
            'name' => $validated['name'],
            'nip' => $validated['nip'] ?? null,
            'email' => $validated['email'],
            'password' => $validated['password'] ?? Str::password(64),
            'auth_source' => $validated['auth_source'],
            'opd_id' => $validated['opd_id'] ?? null,
        ]);
        $user->syncRoles($validated['roles']);
        if ($user->hasRole('trainer')) {
            app(\App\Services\Course\TrainerService::class)->getOrCreateForUser($user);
        }

        return to_route('admin.management.user.index')->with('success', 'Pengguna baru berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $this->ensureSuperAdmin();
        abort_unless($user->hasRole('administrator') || $user->hasRole('super_admin'), 404);
        abort_if($user->hasRole('super_admin'), 403, 'Role Super Admin tidak dapat diubah.');

        return $this->form($user);
    }

    public function update(Request $request, User $user)
    {
        $this->ensureSuperAdmin();
        abort_unless($user->hasRole('administrator') || $user->hasRole('super_admin'), 404);
        abort_if($user->hasRole('super_admin'), 403, 'Role Super Admin tidak dapat diubah.');

        $validated = $this->validateUser($request, $user);

        if ($user->usesLocalAuthentication()) {
            $user->update(array_filter([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'] ?? null,
            ]));
        }
        if ($user->usesLocalAuthentication() && array_key_exists('opd_id', $validated)) {
            $user->update(['opd_id' => $validated['opd_id']]);
        }
        $user->syncRoles($user->getRoleNames()->merge($validated['roles'])->unique()->all());
        if ($user->hasRole('trainer')) {
            app(\App\Services\Course\TrainerService::class)->getOrCreateForUser($user);
        }

        return to_route('admin.management.user.index')->with('success', 'User has been updated.');
    }

    public function destroy(User $user)
    {
        $this->ensureSuperAdmin();
        abort_unless($user->hasRole('administrator') || $user->hasRole('super_admin'), 404);
        abort_if($user->hasRole('super_admin'), 403, 'Pengguna Super Admin tidak dapat dihapus.');

        $user->delete();

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
            'opds' => \App\Models\Identity\Opd::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'available_roles' => $this->availableRoles($user),
            // Compatibility with the already-loaded development module.
            'roles' => $this->availableRoles($user),
        ]);
    }

    private function validateUser(Request $request, ?User $user = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:50', Rule::unique('users', 'nip')->ignore($user?->id)],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'password' => $user || $request->input('auth_source') === User::AUTH_SOURCE_SSO
                ? ['nullable', 'string', 'min:8', 'confirmed']
                : ['required', 'string', 'min:8', 'confirmed'],
            'opd_id' => ['nullable', 'integer', Rule::exists('opds', 'id')->whereNull('deleted_at')->where('is_active', true)],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [
                'string',
                Rule::exists('roles', 'name')->where(fn ($query) => $query
                    ->where('guard_name', 'web')
                    ->where('name', 'administrator')),
            ],
        ];

        if (! $user) {
            $rules['auth_source'] = ['required', Rule::in([User::AUTH_SOURCE_LOCAL, User::AUTH_SOURCE_SSO])];
        }

        if ($user?->usesSsoAuthentication()) {
            unset($rules['name'], $rules['nip'], $rules['email'], $rules['password'], $rules['opd_id']);
        }

        return $request->validate($rules, [], ['name' => 'Nama pengguna']);
    }

    private function ensureSuperAdmin(): void
    {
        abort_unless(request()->user()?->hasRole('super_admin'), 403);
    }

    /** @return array<int, array{id: int, name: string, label: string}> */
    private function availableRoles(?User $user = null): array
    {
        $query = Role::query()
            ->where('guard_name', 'web')
            ->where('name', 'administrator');

        return $query
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(fn (Role $role) => [
                'id' => $role->id,
                'name' => $role->name,
                'label' => Str::headline($role->name),
            ])
            ->values()
            ->all();
    }
}
