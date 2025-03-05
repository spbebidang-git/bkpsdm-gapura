<?php

use App\Http\Middleware\CheckRoleAuthMiddleware;
use App\Http\Middleware\IsFinishedMiddleware;
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
        # Rating & Submit Score
        Route::get('learn/{course:slug}/{scheduleId}/rating', [\App\Http\Controllers\Participant\RatingController::class, 'index'])->name('rating.index')->middleware(IsFinishedMiddleware::class);
        Route::post('learn/{course:slug}/{scheduleId}/rating', [\App\Http\Controllers\Participant\RatingController::class, 'store'])->name('rating.store');
        # Survey
        Route::get('learn/{course:slug}/{scheduleId}/survey', [\App\Http\Controllers\Participant\SurveyController::class, 'index'])->name('survey.index')->middleware(IsFinishedMiddleware::class);;
        # Learn 
        # Instruction
        Route::get('learn/{course:slug}/{schedule}/{activity}/instruction', [\App\Http\Controllers\Participant\LearnController::class, 'instruction'])->name('learn.instruction.index');
        Route::post('learn/{course:slug}/{schedule}/{activity}/instruction', [\App\Http\Controllers\Participant\LearnController::class, 'storeInstruction'])->name('learn.instruction.store');
        # Material
        Route::get('learn/{course:slug}/{schedule}/{activity}', [\App\Http\Controllers\Participant\LearnController::class, 'index'])->name('learn.index');
        Route::post('learn/{course:slug}/{schedule}/{activity}', [\App\Http\Controllers\Participant\LearnController::class, 'store'])->name('learn.store');
        # Result
        Route::get('learn/{course:slug}/{schedule}/{activity}/result', [\App\Http\Controllers\Participant\LearnController::class, 'result'])->name('learn.result.index');
        Route::post('learn/{course:slug}/{schedule}/{activity}/result', [\App\Http\Controllers\Participant\LearnController::class, 'storeResult'])->name('learn.result.store');

        # Profile
        Route::get('/profile', [\App\Http\Controllers\Participant\ProfileController::class, 'index'])->name('profile.index');
        # Certificate
        Route::post('/certificate', \App\Http\Controllers\Participant\CertificateController::class)->name('certificate');
    });
