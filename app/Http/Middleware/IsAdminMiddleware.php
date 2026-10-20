<?php

namespace App\Http\Middleware;

use App\Models\Identity\User;
use App\Models\Course\Course;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class IsAdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($this->shouldBypassAuthentication($request)) {
            $admin = $this->developmentAdmin();

            abort_unless($admin, 503, 'Admin development access is enabled, but no Admin user is available.');

            // Authenticate only for this request. No session or remember-me
            // cookie is written, so disabling DEV_ADMIN_BYPASS restores the
            // normal sign-in requirement immediately.
            Auth::onceUsingId($admin->getKey());
        }

        $user = $request->user();

        if ($user && $this->mayAccessAdminPanel($user)) {
            view()->share('admin', $user);

            if (! $request->routeIs('admin.*')) {
                return to_route('admin.dashboard');
            }

            if ($this->isRestrictedTrainer($user) && ! $user->opd_id) {
                $message = 'Akun trainer Anda belum memiliki OPD. Hubungi administrator untuk melengkapi data OPD.';

                if (! $request->routeIs('admin.dashboard')) {
                    return to_route('admin.dashboard')->with('danger', $message);
                }

                $request->session()->flash('danger', $message);
            }

            if (! $user->hasRole('super_admin') && ($permission = $this->requiredPermission($request)) && ! $user->can($permission)) {
                abort(403, 'Anda tidak memiliki hak akses untuk halaman ini.');
            }

            if ($this->isRestrictedTrainer($user) && ! $this->requiredPermission($request)) {
                abort(403, 'Anda tidak memiliki hak akses untuk halaman ini.');
            }

            if ($this->isRestrictedTrainer($user)) {
                $course = $request->route('course');
                if ($course instanceof Course) {
                    abort_unless($user->opd?->is_active && (string) $course->opd_id === (string) $user->opd_id, 403, 'Trainer hanya dapat mengakses kelas pada OPD aktifnya.');
                    abort_unless($course->trainers()->where('user_id', $user->id)->exists(), 403, 'Trainer hanya dapat mengakses kelas yang ditugaskan.');
                }
            }

            return $next($request);
        }

        if ($user && $user->hasRole('participant')) {
            return to_route('participant.dashboard');
        }

        return to_route('sign-in.index');
    }

    private function shouldBypassAuthentication(Request $request): bool
    {
        // Development access must never replace an authenticated user's identity.
        return $request->user() === null
            && app()->environment('local')
            && config('app.dev_admin_bypass');
    }

    private function developmentAdmin(): ?User
    {
        $userId = config('app.dev_admin_user_id');

        $query = User::whereHas('roles', fn ($roles) => $roles->where('name', 'super_admin')->where('guard_name', 'web'))->orderBy('id');

        if ($userId !== null && $userId !== '') {
            return $query->whereKey($userId)->first();
        }

        $admin = $query->first();

        if ($admin) {
            return $admin;
        }

        // DEV_ADMIN_BYPASS is local-only. Provision an isolated account on a
        // newly migrated local database so visiting /admin is immediately
        // usable without running the demo seeders.
        $admin = User::withTrashed()->firstOrCreate(
            ['email' => 'admin@local.test'],
            [
                'name' => 'Local Super Admin',
                'nip' => 'admin-local',
                'password' => '12345678',
                'auth_source' => User::AUTH_SOURCE_LOCAL,
            ],
        );

        if ($admin->trashed()) {
            $admin->restore();
        }

        Role::findOrCreate('super_admin', 'web');
        $admin->syncRoles(['super_admin']);

        return $admin;
    }

    private function requiredPermission($request): ?string
    {
        $route = (string) ($request->route()?->getName() ?? '');
        $permissions = [
            'admin.dashboard' => 'dashboard.view',
            'admin.management.role' => 'role.manage',
            'admin.management.permission' => 'permission.manage',
            'admin.management.user' => 'user.manage',
            'admin.management.setting' => 'setting.manage',
            'admin.management.notification-messages' => 'notification-template.manage',
            'admin.management.log' => 'log.view',
            'admin.certificate' => 'certificate.manage',
            'admin.bulk-certificate' => 'certificate.manage',
            'admin.import-learn' => 'import.manage',
            'admin.review' => 'review.manage',
            'admin.help' => 'help.manage',
            'admin.category.index' => 'category.view',
            'admin.category.sync' => 'category.manage',
            'admin.opd' => 'opd.manage',
            'admin.trainer' => 'trainer.manage',
            'admin.instructor' => 'trainer.manage',
            'admin.participant.create' => 'participant.manage',
            'admin.participant.store' => 'participant.manage',
            'admin.participant.edit' => 'participant.manage',
            'admin.participant.update' => 'participant.manage',
            'admin.participant.destroy' => 'participant.manage',
            'admin.participant' => 'participant.view',
            'admin.course.activity' => 'course.activity.manage',
            'admin.course.activities' => 'course.activity.manage',
            'admin.course.schedules.index' => 'course.view',
            'admin.course.schedules' => 'course.update',
            'admin.course.create' => 'course.create',
            'admin.course.store' => 'course.create',
            'admin.course.edit' => 'course.update',
            'admin.course.update' => 'course.update',
            'admin.course.destroy' => 'course.delete',
            'admin.course.lock' => 'course.update',
            'admin.course.overview' => 'course.participant.view',
            'admin.course' => 'course.view',
            'admin.result' => 'result.view',
            'admin.course-performance' => 'result.view',
            'admin.report' => 'result.view',
        ];

        foreach ($permissions as $prefix => $permission) {
            if ($route === $prefix || str_starts_with($route, $prefix.'.')) {
                return $permission;
            }
        }

        return null;
    }

    private function mayAccessAdminPanel(User $user): bool
    {
        return $user->getRoleNames()
            ->contains(fn (string $role) => $role !== 'participant');
    }

    private function isRestrictedTrainer(User $user): bool
    {
        return $user->hasRole('trainer')
            && $user->getRoleNames()->every(fn (string $role) => in_array($role, ['trainer', 'participant'], true));
    }
}
