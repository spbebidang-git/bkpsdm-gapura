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
        $this->app->extend('log', fn ($manager, $app) => new \App\Logging\SecureLogManager($app));
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
            $username = Str::lower(trim((string) $request->input('email')));
            $identityKey = hash('sha256', $username . '|' . $request->ip());

            $response = fn () => back()->withErrors([
                'email_or_username' => 'Terlalu banyak percobaan login. Silakan tunggu beberapa saat lalu coba lagi.',
            ])->onlyInput('email');

            return [
                Limit::perMinute(5)
                    ->by('sign-in:' . $identityKey)
                    ->response($response),
                Limit::perMinute(30)->by('sign-in-office:' . $request->ip())->response($response),
            ];
        });

        foreach ([\Illuminate\Auth\Events\Login::class, \Illuminate\Auth\Events\Logout::class, \Illuminate\Auth\Events\Failed::class, \Illuminate\Auth\Events\PasswordReset::class] as $eventClass) {
            \Illuminate\Support\Facades\Event::listen($eventClass, function ($event) {
                \Illuminate\Support\Facades\Log::channel('auth')->log($event instanceof \Illuminate\Auth\Events\Failed ? 'warning' : 'info', 'Peristiwa autentikasi diproses.', [
                    'event' => 'auth.'.Str::snake(class_basename($event)),
                    'actor_id' => $event->user?->getAuthIdentifier(),
                    'guard' => $event->guard ?? null,
                    // Credentials and login identifiers are deliberately excluded.
                ]);
            });
        }

        Vite::prefetch(concurrency: 3);

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        };

        Inertia::share([
            'redirect_url' => fn() => session('redirect_url'),
        ]);
    }
}
