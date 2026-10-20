<?php

use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\Course\ActivityController;
use App\Http\Controllers\Admin\Course\Interactive\OnlineMeetingController;
use App\Http\Controllers\Admin\Course\Interactive\PresentationVideoController;
use App\Http\Controllers\Admin\Course\Literature\LiteratureController;
use App\Http\Controllers\Admin\Course\Question\ImportQuestionController;
use App\Http\Controllers\Admin\Course\Question\PostTestController;
use App\Http\Controllers\Admin\Course\Question\PreTestController;
use App\Http\Controllers\Admin\Course\Question\QuestionItemController;
use App\Http\Controllers\Admin\Course\Question\VariantController;
use App\Http\Controllers\Admin\Course\ScheduleController;
use App\Http\Controllers\Admin\Course\Task\TaskController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\EmployeeStatusController;
use App\Http\Controllers\Admin\InstructorController;
use App\Http\Controllers\Admin\OpdController;
use App\Http\Controllers\Admin\ParticipantController;
use App\Http\Controllers\Admin\TrainerController;
use Illuminate\Support\Facades\Route;

// 2. Master
// Category — all admin roles may view (`category.view`); sync requires `category.manage`.
Route::get('category', [CategoryController::class, 'index'])->name('category.index');
Route::post('category/sync', [CategoryController::class, 'sync'])->name('category.sync');

// OPD — SSO mirror, read-only + sync. Access: super_admin, administrator (`opd.manage`)
Route::get('opd', [OpdController::class, 'index'])->name('opd.index');
Route::post('opd/sync', [OpdController::class, 'sync'])->name('opd.sync');

// Employee status. Access: super_admin, administrator (`employee-status.manage`)
Route::resource('employee-status', EmployeeStatusController::class)
    ->except('show')
    ->parameters(['employee-status' => 'employeeStatus']);

// Trainer. Access: super_admin, administrator (`trainer.manage`)
Route::resource('trainer', TrainerController::class)->except('show');

// Instructor — deferred (nav hidden), keep for now. Access: super_admin, administrator (`trainer.manage`)
Route::resource('instructor', InstructorController::class)->except('show');

// Participant. index: +trainer (`participant.view`); writes: super_admin, administrator (`participant.manage`)
Route::resource('participant', ParticipantController::class)->except('show');

// Course — index + nested management (ex-instructor) inside the admin panel.
Route::get('course', [CourseController::class, 'index'])->name('course.index');

Route::prefix('course')
    ->name('course.')
    ->group(function () {
        // Access: super_admin, administrator, trainer (`course.create`)
        Route::controller(CourseController::class)->group(function () {
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');

            // Access: super_admin, administrator, trainer (`course.participant.view`)
            Route::get('/{course:slug}/overview', 'overview')->name('overview');

            // Access: super_admin, administrator, trainer (`course.view`)
            Route::get('/{course:slug}', 'show')->name('show');

            // Access: super_admin, administrator, trainer (`course.update`)
            Route::get('/{course:slug}/edit', 'edit')->name('edit');
            Route::put('/{course:slug}', 'update')->name('update');
            Route::post('/{course:slug}/lock', 'lock')->name('lock');

            // Access: super_admin, administrator (`course.delete`)
            Route::delete('/{course:slug}', 'destroy')->name('destroy');
        });

        // Schedules. index: `course.view`; writes: `course.update`. Access: super_admin, administrator, trainer
        Route::controller(ScheduleController::class)->prefix('/{course:slug}/schedules')->name('schedules.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::put('/{schedule}', 'update')->name('update');
            Route::delete('/{schedule}', 'destroy')->name('destroy');
        });

        // Access: super_admin, administrator, trainer (`course.activity.manage`)
        Route::get('/{course:slug}/activities', [ActivityController::class, 'management'])->name('activities.index');

        // Activity management. Access: super_admin, administrator, trainer (`course.activity.manage`)
        Route::prefix('/{course:slug}/activity')
            ->name('activity.')
            ->middleware('course.lock')
            ->group(function () {
                Route::controller(ActivityController::class)->group(function () {
                    Route::get('/', 'index')->name('activity');
                    Route::delete('/{activityId}', 'destroy')->name('destroy');
                });

                // Pre/Post test
                Route::resource('pre-test', PreTestController::class);
                Route::resource('post-test', PostTestController::class);

                // Question import
                Route::controller(ImportQuestionController::class)->prefix('{activity}/import-question')->name('import-question.')->group(function () {
                    Route::get('/', 'index')->name('index');
                    Route::get('/download', 'download')->name('download');
                    Route::post('/', 'store')->name('store');
                });

                // Question variant + items
                Route::prefix('variant/{activityId}')
                    ->name('variant.')
                    ->group(function () {
                        Route::controller(VariantController::class)->group(function () {
                            Route::get('/', 'index')->name('index');
                            Route::get('/create', 'create')->name('create');
                            Route::post('/', 'store')->name('store');
                            Route::get('/{variant}/edit', 'edit')->name('edit');
                            Route::put('/{variant}', 'update')->name('update');
                            Route::delete('/{variant}', 'destroy')->name('destroy');
                        });
                        Route::resource('/{variant}/question-item', QuestionItemController::class);
                    });

                // Task
                Route::get('task/{activityId}/result', [TaskController::class, 'result'])->name('result');
                Route::resource('task', TaskController::class);

                // Interactive: meeting + video
                Route::post('/{activityId}/meeting/end-meeting/', [OnlineMeetingController::class, 'endMeeting'])->name('meeting.end-meeting');
                Route::resource('meeting', OnlineMeetingController::class);
                Route::resource('video', PresentationVideoController::class);

                // Literature
                Route::resource('literature', LiteratureController::class);
            });
    });
