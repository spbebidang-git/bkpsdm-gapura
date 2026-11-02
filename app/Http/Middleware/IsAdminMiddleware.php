<?php

namespace App\Http\Middleware;

use App\Models\Identity\User;
use App\Models\Course\Course;
use App\Models\Course\Trainer;
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

            if ($request->routeIs('admin.management.permission.*', 'admin.management.log.*')) {
                abort_unless($user->hasRole('super_admin'), 403, 'Halaman ini hanya dapat diakses Super Admin.');
            }

            if ($this->isRestrictedTrainer($user) && ! Trainer::activeOpdIdsForUser($user->id)) {
                $message = 'Akun pemateri Anda belum memiliki OPD. Hubungi administrator untuk melengkapi data OPD.';

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
                    abort_unless(app(\App\Services\Course\CourseAudience::class)->canManage($course, $user), 403);
                    abort_unless($course->trainers()->where('user_id', $user->id)->exists(), 403, 'Pemateri hanya dapat mengakses kelas yang ditugaskan.');
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
            'admin.management.role' => 'role.view',
            'admin.management.permission' => 'permission.view',
            'admin.management.user' => 'user.view',
            'admin.management.setting' => 'setting.view',
            'admin.management.notification-messages' => 'notification-template.view',
            'admin.management.log' => 'log.view',
            'admin.certificate' => 'certificate.view',
            'admin.bulk-certificate' => 'certificate.view',
            'admin.import-learn' => 'import.view',
            'admin.review' => 'review.view',
            'admin.help' => 'help.view',
            'admin.category.index' => 'category.view',
            'admin.category.sync' => 'category.sync',
            'admin.opd' => 'opd.view',
            'admin.trainer.create' => 'trainer.create',
            'admin.trainer.store' => 'trainer.create',
            'admin.trainer.edit' => 'trainer.edit',
            'admin.trainer.update' => 'trainer.edit',
            'admin.trainer.destroy' => 'trainer.delete',
            'admin.trainer' => 'trainer.view',
            'admin.instructor.create' => 'instructor.create',
            'admin.instructor.store' => 'instructor.create',
            'admin.instructor.edit' => 'instructor.edit',
            'admin.instructor.update' => 'instructor.edit',
            'admin.instructor.destroy' => 'instructor.delete',
            'admin.instructor' => 'instructor.view',
            'admin.participant.create' => 'participant.create',
            'admin.participant.store' => 'participant.create',
            'admin.participant.edit' => 'participant.edit',
            'admin.participant.update' => 'participant.edit',
            'admin.participant.destroy' => 'participant.delete',
            'admin.participant' => 'participant.view',
            'admin.course.activity' => 'course.activity.view',
            'admin.course.activities' => 'course.activity.view',
            'admin.course.schedules.index' => 'course.view',
            'admin.course.schedules' => 'course.edit',
            'admin.course.create' => 'course.create',
            'admin.course.store' => 'course.create',
            'admin.course.edit' => 'course.edit',
            'admin.course.update' => 'course.edit',
            'admin.course.destroy' => 'course.delete',
            'admin.course.lock' => 'course.edit',
            'admin.course.unlock' => 'course.edit',
            'admin.course.overview' => 'course.participant.view',
            'admin.course' => 'course.view',
            'admin.result' => 'result.view',
            'admin.course-performance' => 'result.view',
            'admin.report' => 'result.view',
        ];

        // Nested activity resources (pre-test, task, literature, variant, ...)
        // each carry their own create/edit/delete permission. Administrator may
        // only edit existing activities, never add or remove them, so this check
        // must run before the generic `admin.course.activity` prefix below.
        if (str_starts_with($route, 'admin.course.activity.')) {
            return match (true) {
                $route === 'admin.course.activity.activity' => 'course.activity.create',
                str_ends_with($route, '.create'), str_ends_with($route, '.store') => 'course.activity.create',
                str_ends_with($route, '.edit'), str_ends_with($route, '.update') => 'course.activity.edit',
                str_ends_with($route, '.destroy') => 'course.activity.delete',
                str_ends_with($route, '.end-meeting') => 'course.activity.edit',
                default => 'course.activity.view',
            };
        }

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
