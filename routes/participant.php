<?php

use App\Http\Middleware\CheckRoleAuthMiddleware;
use App\Http\Middleware\IsFinishedMiddleware;
use App\Http\Middleware\IsParticipantMiddleware;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Participant\NotificationController;

Route::middleware(['auth', CheckRoleAuthMiddleware::class, IsParticipantMiddleware::class])
    ->name('participant.')
    ->group(function () {
        Route::get('notifications/count', [NotificationController::class, 'count'])->name('notifications.count');
        Route::get('notifications/latest', [NotificationController::class, 'latest'])->name('notifications.latest');
        Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::patch('notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
        Route::patch('notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
        # Dashboard
        Route::get('dashboard', \App\Http\Controllers\Participant\DashboardController::class)->name('dashboard');
        # My Course 
        Route::get('my-course', [\App\Http\Controllers\Participant\CourseController::class, 'index'])->name('course.index');
        Route::get('my-course/{course:slug}', [\App\Http\Controllers\Participant\CourseController::class, 'show'])->name('course.show');
        # Rating & Submit Score
        Route::get('learn/{course:slug}/{scheduleId}/rating', [\App\Http\Controllers\Participant\RatingController::class, 'index'])->name('rating.index')->middleware([IsFinishedMiddleware::class, 'participant.enrollment:read']);
        Route::post('learn/{course:slug}/{scheduleId}/rating', [\App\Http\Controllers\Participant\RatingController::class, 'store'])->name('rating.store')->middleware('participant.enrollment:write');
        # Survey
        Route::get('learn/{course:slug}/{scheduleId}/survey', [\App\Http\Controllers\Participant\SurveyController::class, 'index'])->name('survey.index')->middleware([IsFinishedMiddleware::class, 'participant.enrollment:read']);
        # Learn 
        # Instruction
        Route::get('learn/{course:slug}/{schedule}/{activity}/instruction', [\App\Http\Controllers\Participant\LearnController::class, 'instruction'])->name('learn.instruction.index')->middleware('participant.enrollment:read');
        Route::post('learn/{course:slug}/{schedule}/{activity}/instruction', [\App\Http\Controllers\Participant\LearnController::class, 'storeInstruction'])->name('learn.instruction.store')->middleware('participant.enrollment:write');
        # Material
        Route::get('learn/{course:slug}/{schedule}/{activity}', [\App\Http\Controllers\Participant\LearnController::class, 'index'])->name('learn.index')->middleware('participant.enrollment:read');
        Route::post('learn/{course:slug}/{schedule}/{activity}', [\App\Http\Controllers\Participant\LearnController::class, 'store'])->name('learn.store')->middleware('participant.enrollment:write');
        # Result
        Route::get('learn/{course:slug}/{schedule}/{activity}/result', [\App\Http\Controllers\Participant\LearnController::class, 'result'])->name('learn.result.index')->middleware('participant.enrollment:read');
        Route::post('learn/{course:slug}/{schedule}/{activity}/result', [\App\Http\Controllers\Participant\LearnController::class, 'storeResult'])->name('learn.result.store')->middleware('participant.enrollment:write');

        # Profile
        Route::get('/profile', [\App\Http\Controllers\Participant\ProfileController::class, 'index'])->name('profile.index');
        # Certificate
        Route::post('/certificate', \App\Http\Controllers\Participant\CertificateController::class)->name('certificate');
    });
