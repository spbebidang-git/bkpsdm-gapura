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
|
| Semua endpoint di bawah ini memerlukan Bearer token via Sanctum.
| Gunakan POST /api/v1/login untuk mendapatkan token.
|
*/

// Auth endpoints (public - tidak perlu token)
Route::prefix('v1')->name('api.v1.')->middleware([DisableApiCookies::class, 'throttle:10,1'])->group(function () {
    Route::post('login', [AuthController::class, 'login'])->name('login');
});

Route::prefix('v1')
    ->name('api.v1.')
    ->middleware([DisableApiCookies::class, 'auth:sanctum', 'throttle:60,1'])
    ->group(function () {

        // Auth (perlu token)
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        // ==========================================
        // Courses (Pelatihan)
        // ==========================================
        Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
        Route::get('courses/{slug}', [CourseController::class, 'show'])->name('courses.show');
        Route::get('courses/{slug}/participants', [CourseController::class, 'participants'])->name('courses.participants');
        Route::get('courses/{slug}/statistics', [CourseController::class, 'statistics'])->name('courses.statistics');
        Route::get('courses/{slug}/results', [CourseController::class, 'results'])->name('courses.results');

        // ==========================================
        // Categories (Kategori)
        // ==========================================
        Route::get('categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('categories/{id}/courses', [CategoryController::class, 'courses'])->name('categories.courses');

        // ==========================================
        // Participants (Peserta)
        // ==========================================
        Route::get('participants', [ParticipantController::class, 'index'])->name('participants.index');
        Route::get('participants/{id}', [ParticipantController::class, 'show'])->name('participants.show');

        // ==========================================
        // Statistics (Statistik)
        // ==========================================
        Route::get('statistics/overview', [StatisticController::class, 'overview'])->name('statistics.overview');
        Route::get('statistics/yearly', [StatisticController::class, 'yearly'])->name('statistics.yearly');
        Route::get('statistics/employee-status', [StatisticController::class, 'employeeStatus'])->name('statistics.employee-status');
    });
