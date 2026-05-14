<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('sign-in', function (Request $request) {
            $username = Str::lower(trim((string) $request->input('username')));
            $identityKey = hash('sha256', $username . '|' . $request->ip());

            return [
                Limit::perMinute(15)
                    ->by('sign-in:' . $identityKey)
                    ->response(function () {
                        return back()->withErrors([
                            'email_or_username' => 'Terlalu banyak percobaan login. Silakan tunggu sekitar 1 menit lalu coba lagi.',
                        ]);
                    }),
                Limit::perMinute(500)->by('sign-in-office:' . $request->ip()),
            ];
        });

        Vite::prefetch(concurrency: 3);

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        };

        Inertia::share([
            'redirect_url' => fn() => session('redirect_url'),
        ]);
    }
}
