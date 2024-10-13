<?php

use App\Http\Middleware\CheckRoleAuthMiddleware;
use App\Http\Middleware\IsInstructorMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('/instructor')
    ->middleware([CheckRoleAuthMiddleware::class, IsInstructorMiddleware::class])
    ->name('instructor.')
    ->group(function () {

        // Dashboard
        Route::get('/', \App\Http\Controllers\Instructor\DashboardController::class)->name('dashboard');

        // Course
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
                        Route::get('/', \App\Http\Controllers\Instructor\Course\ActivityController::class)->name('activity');
                        // Pre Test
                        Route::prefix('pre-test')
                            ->name('pre-test')
                            ->group(function () {
                                Route::get('/create', [\App\Http\Controllers\Instructor\Course\Question\PreTestController::class, 'create'])->name('create');
                                Route::post('/', [\App\Http\Controllers\Instructor\Course\Question\PreTestController::class, 'store'])->name('store');
                                Route::get('/{pre-test}/edit', [\App\Http\Controllers\Instructor\Course\Question\PreTestController::class, 'edit'])->name('edit');
                            });
                        // Post Test
                        Route::prefix('post-test')
                            ->name('post-test')
                            ->group(function () {
                                Route::get('/create', [\App\Http\Controllers\Instructor\Course\Question\PostTestController::class, 'create'])->name('create');
                                Route::post('/', [\App\Http\Controllers\Instructor\Course\Question\PostTestController::class, 'store'])->name('store');
                                Route::get('/{post-test}/edit', [\App\Http\Controllers\Instructor\Course\Question\PostTestController::class, 'edit'])->name('edit');
                            });

                        Route::prefix('variant/{activityId}')
                            ->name('variant.')
                            ->group(function () {
                                Route::get('/', [\App\Http\Controllers\Instructor\Course\Question\VariantController::class, 'index'])->name('index');
                                Route::get('/create', [\App\Http\Controllers\Instructor\Course\Question\VariantController::class, 'create'])->name('create');
                                Route::post('/', [\App\Http\Controllers\Instructor\Course\Question\VariantController::class, 'store'])->name('store');
                                Route::get('/{variant}/edit', [\App\Http\Controllers\Instructor\Course\Question\VariantController::class, 'edit'])->name('edit');
                                Route::put('/{variant}', [\App\Http\Controllers\Instructor\Course\Question\VariantController::class, 'update'])->name('update');
                                Route::delete('/{variant}', [\App\Http\Controllers\Instructor\Course\Question\VariantController::class, 'destroy'])->name('destroy');

                                // Set Question / Question Item
                                Route::resource('/{variant}/question-item', \App\Http\Controllers\Instructor\Course\Question\QuestionItemController::class);
                            });
                        // Task
                        Route::prefix('task')
                            ->name('task')
                            ->group(function () {
                                Route::get('/create', [\App\Http\Controllers\Instructor\Course\Task\TaskController::class, 'create'])->name('create');
                                Route::post('/', [\App\Http\Controllers\Instructor\Course\Task\TaskController::class, 'store'])->name('store');
                                Route::get('/{task}/edit', [\App\Http\Controllers\Instructor\Course\Task\TaskController::class, 'edit'])->name('edit');
                            });
                        // Meeting
                        Route::prefix('meeting')
                            ->name('meeting')
                            ->group(function () {
                                Route::get('/create', [\App\Http\Controllers\Instructor\Course\Interactive\OnlineMeetingController::class, 'create'])->name('create');
                                Route::post('/', [\App\Http\Controllers\Instructor\Course\Interactive\OnlineMeetingController::class, 'store'])->name('store');
                                Route::get('/{meeting}/edit', [\App\Http\Controllers\Instructor\Course\Interactive\OnlineMeetingController::class, 'edit'])->name('edit');
                            });
                        // Video Interactive
                        Route::prefix('presentation-video')
                            ->name('presentation-video')
                            ->group(function () {
                                Route::get('/create', [\App\Http\Controllers\Instructor\Course\Interactive\PresentationVideoController::class, 'create'])->name('create');
                                Route::post('/', [\App\Http\Controllers\Instructor\Course\Interactive\PresentationVideoController::class, 'store'])->name('store');
                                Route::get('/{presentation-video}/edit', [\App\Http\Controllers\Instructor\Course\Interactive\PresentationVideoController::class, 'edit'])->name('edit');
                            });
                        // Literature
                        Route::prefix('literature')
                            ->name('literature')
                            ->group(function () {
                                Route::get('/create', [\App\Http\Controllers\Instructor\Course\Literature\LiteratureController::class, 'create'])->name('create');
                                Route::post('/', [\App\Http\Controllers\Instructor\Course\Literature\LiteratureController::class, 'store'])->name('store');
                                Route::get('/{literature}/edit', [\App\Http\Controllers\Instructor\Course\Literature\LiteratureController::class, 'edit'])->name('edit');
                            });
                    });
            });
    });
