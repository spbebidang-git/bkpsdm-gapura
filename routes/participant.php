<?php

use App\Http\Middleware\CheckRoleAuthMiddleware;
use App\Http\Middleware\IsParticipantMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware([CheckRoleAuthMiddleware::class, IsParticipantMiddleware::class])
    ->name('participant.')
    ->group(function () {
        # Dashboard
        Route::get('dashboard', \App\Http\Controllers\Participant\DashboardController::class)->name('dashboard');
        # My Course 
        Route::get('my-course', [\App\Http\Controllers\Participant\CourseController::class, 'index'])->name('course.index');
        Route::get('my-course/{course:slug}', [\App\Http\Controllers\Participant\CourseController::class, 'show'])->name('course.show');
        # Learn 
        Route::get('learn/{slug}/{scheduleId}/{activity}', [\App\Http\Controllers\Participant\LearnController::class, 'index'])->name('learn.index');
        Route::post('learn/{course:slug}/{scheduleId}/{activity}', [\App\Http\Controllers\Participant\LearnController::class, 'store'])->name('learn.store');
        # Profile
        Route::get('/profile', [\App\Http\Controllers\Participant\ProfileController::class, 'index'])->name('profile.index');
    });
