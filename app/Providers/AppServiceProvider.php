<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
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
        Validator::extend('captcha', function (string $attribute, mixed $value): bool {
            if (config('captcha.disable')) {
                return true;
            }

            return is_string($value)
                && hash_equals((string) session('app_captcha.answer'), strtoupper($value));
        });

        Validator::extend('captcha_api', function (string $attribute, mixed $value, array $parameters): bool {
            if (config('captcha.disable')) {
                return true;
            }

            $key = $parameters[0] ?? null;
            $answer = session('app_captcha.answer');

            if (! is_string($key) || ! is_string($answer) || ! hash_equals((string) session('app_captcha.key'), $key)) {
                return false;
            }

            $isValid = is_string($value) && hash_equals($answer, strtoupper($value));
            session()->forget('app_captcha');

            return $isValid;
        });

        RateLimiter::for('sign-in', function (Request $request) {
            $username = Str::lower(trim((string) $request->input('username')));
            $identityKey = hash('sha256', $username . '|' . $request->ip());

            return [
                Limit::perMinute(5)
                    ->by('sign-in:' . $identityKey)
                    ->response(function () {
                        return back()->withErrors([
                            'email_or_username' => 'Terlalu banyak percobaan login. Silakan tunggu sekitar 1 menit lalu coba lagi.',
                        ]);
                    }),
                Limit::perMinute(30)->by('sign-in-office:' . $request->ip()),
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
