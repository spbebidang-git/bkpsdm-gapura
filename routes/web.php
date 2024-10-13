<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Sign In / Guest
Route::prefix('/sign-in')
    ->name('sign-in.')
    ->middleware('guest')
    ->group(function () {
        // Admin
        Route::get('/admin', [\App\Http\Controllers\Web\AuthController::class, 'createAdmin'])->name('admin.create');
        Route::post('/admin', [\App\Http\Controllers\Web\AuthController::class, 'storeAdmin'])->name('admin.store');
        // Instructor
        Route::get('/instructor', [\App\Http\Controllers\Web\AuthController::class, 'createInstructor'])->name('instructor.create');
        Route::post('/instructor', [\App\Http\Controllers\Web\AuthController::class, 'storeInstructor'])->name('instructor.store');
        // Participant
        Route::get('/', [\App\Http\Controllers\Web\AuthController::class, 'createParticipant'])->name('participant.create');
        Route::post('/', [\App\Http\Controllers\Web\AuthController::class, 'storeParticipant'])->name('participant.store');
    });
// Sign Out
Route::post('/sign-out', [\App\Http\Controllers\Web\AuthController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

// Home
Route::get('/', \App\Http\Controllers\Web\HomeController::class)->name('home');
// Course
Route::get('/course/{course:slug}', [\App\Http\Controllers\Web\CourseController::class, 'show'])->name('course.show');
Route::post('/course/{schedule}', [\App\Http\Controllers\Web\CourseController::class, 'store'])->name('course.store');
// Category
Route::get('/category', [\App\Http\Controllers\Web\CategoryController::class, 'index'])->name('category.index');
Route::get('/category/{category}', [\App\Http\Controllers\Web\CategoryController::class, 'show'])->name('category.show');

// Customer 
// Route::prefix('learn')

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';

require __DIR__ . '/participant.php';
require __DIR__ . '/instructor.php';
require __DIR__ . '/admin.php';
