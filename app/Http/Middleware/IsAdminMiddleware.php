<?php

namespace App\Http\Middleware;

use App\Models\Identity\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class IsAdminMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($this->shouldBypassAuthentication()) {
            $admin = $this->developmentAdmin();

            abort_unless($admin, 503, 'Admin development access is enabled, but no Admin user is available.');

            // Authenticate only for this request. No session or remember-me
            // cookie is written, so disabling DEV_ADMIN_BYPASS restores the
            // normal sign-in requirement immediately.
            Auth::onceUsingId($admin->getKey());
        }

        $user = $request->user();

        if ($user && $user->hasRole('Admin')) {
            view()->share('admin', $user);

            if (! $request->routeIs('admin.*')) {
                return to_route('admin.dashboard');
            }

            return $next($request);
        }

        if ($user && $user->hasRole('Participant')) {
            return to_route('home');
        }

        return to_route('sign-in.index');
    }

    private function shouldBypassAuthentication(): bool
    {
        return app()->environment('local')
            && config('app.dev_admin_bypass');
    }

    private function developmentAdmin(): ?User
    {
        $userId = config('app.dev_admin_user_id');

        $query = User::role('Admin')->orderBy('id');

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
            ['name' => 'Local Admin', 'nip' => 'admin-local'],
        );

        if ($admin->trashed()) {
            $admin->restore();
        }

        $admin->syncRoles(['Admin']);

        return $admin;
    }
}
