<?php

use App\Http\Controllers\Participant\CertificateController;
use App\Http\Controllers\Participant\CourseController;
use App\Http\Controllers\Participant\DashboardController;
use App\Http\Controllers\Participant\LearnController;
use App\Http\Controllers\Participant\NotificationController;
use App\Http\Controllers\Participant\ProfileController;
use App\Http\Controllers\Participant\RatingController;
use App\Http\Controllers\Participant\SurveyController;
use App\Http\Middleware\CheckRoleAuthMiddleware;
use App\Http\Middleware\IsFinishedMiddleware;
use App\Http\Middleware\IsParticipantMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Participant Routes
|--------------------------------------------------------------------------
| Order follows the participant header nav (resources/js/Layouts/Participant/Header.tsx):
|   Overview    — dashboard
|   My Courses  — course list + detail
|   Notifications
|   Profile
|   Learning flow — rating, survey, instruction, material, result
|   Certificate — issued after finishing a course
|
| Access: `auth` + CheckRoleAuthMiddleware + IsParticipantMiddleware.
| Only the `participant` role reaches these routes; other roles are redirected
| to the admin panel (see IsParticipantMiddleware).
| Learning routes additionally require an active enrollment via
| `participant.enrollment:read|write` (CheckParticipantEnrollmentAccess), and
| rating/survey require the course to be finished (IsFinishedMiddleware).
*/

Route::middleware(['auth', CheckRoleAuthMiddleware::class, IsParticipantMiddleware::class])
    ->name('participant.')
    ->group(function () {
        // Overview. Access: participant
        Route::get('dashboard', DashboardController::class)->name('dashboard');

        // My Courses. Access: participant
        Route::controller(CourseController::class)->prefix('my-course')->name('course.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{course:slug}', 'show')->name('show');
            Route::post('/{course:slug}/reactivate', 'reactivate')->name('reactivate');
            Route::post('/{course:slug}/reset', 'reset')->name('reset');
        });

        // Notifications. Access: participant
        Route::controller(NotificationController::class)->prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/count', 'count')->name('count');
            Route::get('/latest', 'latest')->name('latest');
            Route::get('/', 'index')->name('index');
            Route::patch('/read-all', 'readAll')->name('read-all');
            Route::patch('/{notification}/read', 'read')->name('read');
        });

        // Profile. Access: participant
        Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');

        // Certificate. Access: participant
        Route::post('/certificate', CertificateController::class)->name('certificate');

        // Learning flow — enrollment `read` grants view, `write` grants submit.
        // Rating + survey require the course to be finished (IsFinishedMiddleware).
        // Access: participant
        Route::prefix('learn/{course:slug}')->group(function () {
            // Rating
            Route::controller(RatingController::class)->prefix('{scheduleId}/rating')->name('rating.')->group(function () {
                Route::get('/', 'index')->name('index')->middleware(['participant.enrollment:read', IsFinishedMiddleware::class]);
                Route::post('/', 'store')->name('store')->middleware('participant.enrollment:write');
            });

            // Survey
            Route::get('{scheduleId}/survey', [SurveyController::class, 'index'])
                ->name('survey.index')
                ->middleware(['participant.enrollment:read', IsFinishedMiddleware::class]);

            // Instruction + Material + Result (per activity)
            Route::controller(LearnController::class)->prefix('{schedule}/{activity}')->group(function () {
                // Instruction
                Route::prefix('instruction')->name('learn.instruction.')->group(function () {
                    Route::get('/', 'instruction')->name('index')->middleware('participant.enrollment:read');
                    Route::post('/', 'storeInstruction')->name('store')->middleware('participant.enrollment:write');
                });

                // Material
                Route::get('/', 'index')->name('learn.index')->middleware('participant.enrollment:read');
                Route::post('/', 'store')->name('learn.store')->middleware('participant.enrollment:write');

                // Result
                Route::prefix('result')->name('learn.result.')->group(function () {
                    Route::get('/', 'result')->name('index')->middleware('participant.enrollment:read');
                    Route::post('/', 'storeResult')->name('store')->middleware('participant.enrollment:write');
                });
            });
        });
    });
