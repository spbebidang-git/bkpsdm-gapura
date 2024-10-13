<?php

use App\Http\Middleware\CheckRoleAuthMiddleware;
use App\Http\Middleware\IsAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('/admin')
    ->name('admin.')
    ->middleware([IsAdminMiddleware::class])
    ->group(function () {
        # Dashboard
        Route::get('/', \App\Http\Controllers\Admin\DashboardController::class)->name('dashboard');
        # Participant 
        Route::resource('participant', \App\Http\Controllers\Admin\ParticipantController::class);
        # Instructor 
        Route::resource('instructor', \App\Http\Controllers\Admin\InstructorController::class)->except('show');
        # Category 
        Route::resource('category', \App\Http\Controllers\Admin\CategoryController::class);
        # Course 
        Route::resource('course', \App\Http\Controllers\Admin\CourseController::class)->only('index', 'show');
        # Review
        Route::resource('review', \App\Http\Controllers\Admin\ReviewController::class);

        # Management 
        Route::prefix('management')
            ->name('management.')
            ->group(function () {
                # Role
                Route::resource('role', \App\Http\Controllers\Admin\Management\RoleController::class)->only('index');
                # Permission
                Route::resource('permission', \App\Http\Controllers\Admin\Management\PermissionController::class)->except('show');
                # Setting
                Route::resource('setting', \App\Http\Controllers\Admin\Management\SettingController::class)->only('index', 'store');
                # User
                Route::resource('user', \App\Http\Controllers\Admin\Management\UserController::class)->except('show');
            });
    });
