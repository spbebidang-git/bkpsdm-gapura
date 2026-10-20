<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\CapthaController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\CourseController;
use App\Http\Controllers\Web\HelpController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\StaticPageController;
use App\Http\Middleware\BlockInstructorFromPublicRoutes;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\IsParticipantMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes (public + shared)
|--------------------------------------------------------------------------
| Auth (sign in/out, SSO callback) → public pages → account profile.
| All routes share HandleInertiaRequests. `auth.php`, `participant.php`, and
| `admin.php` are required at the bottom of this file.
|
| Access is enforced by middleware:
|   guest                  — sign-in + Kedaton SSO redirect/callback
|   auth                   — profile update/delete
|   BlockInstructorFromPublicRoutes — keeps admin/trainer off public pages
|   IsParticipantMiddleware — only `participant` may enroll in a course
*/

// Auth::loginUsingId(2);
Route::middleware(HandleInertiaRequests::class)->group(function () {
    // Sign in / Sign out. Access: guest
    Route::middleware('guest')->group(function () {
        Route::controller(AuthController::class)->group(function () {
            Route::get('/sign-in', 'index')->name('sign-in.index');
            Route::post('/sign-in', 'login')->name('sign-in.store')->middleware('throttle:sign-in');
        });

        // Kedaton SSO. Access: guest
        Route::controller(AuthController::class)->prefix('auth/kedaton')->name('kedaton.')->group(function () {
            Route::get('/redirect', 'redirectToKedaton')->name('redirect')->middleware('throttle:30,1');
            Route::get('/callback', 'handleKedatonCallback')->name('callback')->middleware('throttle:30,1');
        });
    });

    // Logout. Access: authenticated
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Public pages. Access: guest + participant; admin/trainer are redirected to the admin panel.
    Route::middleware(BlockInstructorFromPublicRoutes::class)->group(function () {
        // Home
        Route::get('/', HomeController::class)->name('home');

        // About + Contact
        Route::controller(StaticPageController::class)->group(function () {
            Route::get('/about', 'about')->name('about');
            Route::get('/contact', 'contact')->name('contact');
        });

        // Course catalog. Access: guest + participant; enroll requires `participant`.
        Route::controller(CourseController::class)->prefix('course')->name('course.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{course:slug}', 'show')->name('show');
            Route::post('/{course:slug}/{schedule}', 'store')
                ->name('store')
                ->middleware(IsParticipantMiddleware::class);
        });

        // Category
        Route::controller(CategoryController::class)->prefix('category')->name('category.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{category}', 'show')->name('show');
        });

        // Help
        Route::get('/help', [HelpController::class, 'index'])->name('help.index');
    });

    // Captcha. Access: guest
    Route::get('captcha-url', [CapthaController::class, 'captcha'])
        ->middleware('throttle:60,1')
        ->name('captcha');

    // Account profile. Access: authenticated
    Route::middleware('auth')->group(function () {
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    // Grouped route files
    require __DIR__ . '/auth.php';
    require __DIR__ . '/participant.php';
});

require __DIR__ . '/admin.php';
