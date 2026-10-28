<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CourseController;
use App\Http\Controllers\Api\V1\ParticipantController;
use App\Http\Controllers\Api\V1\StatisticController;
use App\Http\Middleware\DisableApiCookies;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Kominfo Integration
|--------------------------------------------------------------------------
| All v1 endpoints use POST. Data endpoints require a Bearer token via Sanctum.
| Filters and pagination are accepted in the JSON request body.
| POST /api/v1/login {username=NIP, password} → SSO → Admin → Sanctum token.
|
| Throttle: login 10/min, authenticated endpoints 60/min. Cookies are disabled
| for all API responses (DisableApiCookies).
*/

// Auth endpoints (public — no token). Access: guest
Route::prefix('v1')
    ->name('api.v1.')
    ->middleware([DisableApiCookies::class, 'throttle:10,1'])
    ->group(function () {
        Route::post('login', [AuthController::class, 'login'])->name('login');
    });

// Authenticated endpoints. Access: `api:read` ability via Sanctum
Route::prefix('v1')
    ->name('api.v1.')
    ->middleware([DisableApiCookies::class, 'auth:sanctum', 'abilities:api:read', 'throttle:60,1'])
    ->group(function () {
        // Auth
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        // Courses (Pelatihan)
        Route::controller(CourseController::class)->prefix('courses')->name('courses.')->group(function () {
            Route::post('/', 'index')->name('index');
            Route::post('/{slug}', 'show')->name('show');
            Route::post('/{slug}/participants', 'participants')->name('participants');
            Route::post('/{slug}/statistics', 'statistics')->name('statistics');
            Route::post('/{slug}/results', 'results')->name('results');
        });

        // Categories (Kategori)
        Route::controller(CategoryController::class)->prefix('categories')->name('categories.')->group(function () {
            Route::post('/', 'index')->name('index');
            Route::post('/{id}/courses', 'courses')->name('courses');
        });

        // Participants (Peserta)
        Route::controller(ParticipantController::class)->prefix('participants')->name('participants.')->group(function () {
            Route::post('/', 'index')->name('index');
            Route::post('/{id}', 'show')->name('show');
        });

        // Statistics (Statistik)
        Route::controller(StatisticController::class)->prefix('statistics')->name('statistics.')->group(function () {
            Route::post('/overview', 'overview')->name('overview');
            Route::post('/yearly', 'yearly')->name('yearly');
            Route::post('/employee-status', 'employeeStatus')->name('employee-status');
        });
    });
