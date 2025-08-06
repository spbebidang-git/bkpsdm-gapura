<?php

use App\Http\Middleware\CheckRoleAuthMiddleware;
use App\Http\Middleware\IsInstructorMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('/instructor')
    ->middleware([CheckRoleAuthMiddleware::class, IsInstructorMiddleware::class])
    ->name('instructor.')
    ->group(function () {

        # Dashboard
        Route::get('/', \App\Http\Controllers\Instructor\DashboardController::class)->name('dashboard');

        # Profile
        Route::get('/profile', [\App\Http\Controllers\Instructor\ProfileController::class, 'index'])->name('profile.index');

        # Course
        Route::prefix('course')
            ->name('course.')
            ->group(function () {
                Route::get('/create', [\App\Http\Controllers\Instructor\CourseController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Instructor\CourseController::class, 'store'])->name('store');
                Route::get('/{course:slug}', [\App\Http\Controllers\Instructor\CourseController::class, 'show'])->name('show');
                Route::get('/{course:slug}/edit', [\App\Http\Controllers\Instructor\CourseController::class, 'edit'])->name('edit');
                Route::put('/{course:slug}', [\App\Http\Controllers\Instructor\CourseController::class, 'update'])->name('update');
                Route::delete('/{course:slug}', [\App\Http\Controllers\Instructor\CourseController::class, 'destroy'])->name('destroy');
                Route::prefix('/{course:slug}/activity')
                    ->name('activity.')
                    ->group(function () {
                        Route::get('/', [\App\Http\Controllers\Instructor\Course\ActivityController::class, 'index'])->name('activity');
                        Route::delete('/{activityId}', [\App\Http\Controllers\Instructor\Course\ActivityController::class, 'destroy'])->name('destroy');
                        # Pre Test
                        Route::resource('pre-test', \App\Http\Controllers\Instructor\Course\Question\PreTestController::class);
                        # Post Test
                        Route::resource('post-test', \App\Http\Controllers\Instructor\Course\Question\PostTestController::class);
                        # Import Question 
                        Route::get('{activity}/import-question', [\App\Http\Controllers\Instructor\Course\Question\ImportQuestionController::class, 'index'])->name('import-question.index');
                        Route::get('{activity}/import-question/download', [\App\Http\Controllers\Instructor\Course\Question\ImportQuestionController::class, 'download'])->name('import-question.download');
                        Route::post('{activity}/import-question', [\App\Http\Controllers\Instructor\Course\Question\ImportQuestionController::class, 'store'])->name('import-question.store');
                        # Variant
                        Route::prefix('variant/{activityId}')
                            ->name('variant.')
                            ->group(function () {
                                Route::get('/', [\App\Http\Controllers\Instructor\Course\Question\VariantController::class, 'index'])->name('index');
                                Route::get('/create', [\App\Http\Controllers\Instructor\Course\Question\VariantController::class, 'create'])->name('create');
                                Route::post('/', [\App\Http\Controllers\Instructor\Course\Question\VariantController::class, 'store'])->name('store');
                                Route::get('/{variant}/edit', [\App\Http\Controllers\Instructor\Course\Question\VariantController::class, 'edit'])->name('edit');
                                Route::put('/{variant}', [\App\Http\Controllers\Instructor\Course\Question\VariantController::class, 'update'])->name('update');
                                Route::delete('/{variant}', [\App\Http\Controllers\Instructor\Course\Question\VariantController::class, 'destroy'])->name('destroy');

                                # Set Question / Question Item
                                Route::resource('/{variant}/question-item', \App\Http\Controllers\Instructor\Course\Question\QuestionItemController::class);
                            });
                        # Task
                        Route::get('task/{activityId}/result', [\App\Http\Controllers\Instructor\Course\Task\TaskController::class, 'result'])->name('result');
                        Route::resource('task', \App\Http\Controllers\Instructor\Course\Task\TaskController::class);
                        # Meeting
                        Route::post('/{activityId}/meeting/end-meeting/', [\App\Http\Controllers\Instructor\Course\Interactive\OnlineMeetingController::class, 'endMeeting'])->name('meeting.end-meeting');
                        Route::resource('meeting', \App\Http\Controllers\Instructor\Course\Interactive\OnlineMeetingController::class);
                        # Video Interactive
                        Route::resource('video', \App\Http\Controllers\Instructor\Course\Interactive\PresentationVideoController::class);
                        # Literature
                        Route::resource('literature', \App\Http\Controllers\Instructor\Course\Literature\LiteratureController::class);
                    });
            });


        # result
        Route::get('result', [\App\Http\Controllers\Instructor\Result\ResultController::class, 'index'])->name('result.index');
        Route::get('result/{course}/post-test', [\App\Http\Controllers\Instructor\Result\ResultController::class, 'postTest'])->name('result.post-test');
        Route::get('result/{course}', [\App\Http\Controllers\Instructor\Result\ResultController::class, 'show'])->name('result.show');
    });
