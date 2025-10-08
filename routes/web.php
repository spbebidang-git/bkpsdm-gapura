<?php

use App\Http\Controllers\ProfileController;
use App\Http\Middleware\IsParticipantMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/dummy', function () {
    Auth::loginUsingId(1212);
});

# Sign In / Guest
Route::prefix('/sign-in')
    ->name('sign-in.')
    ->middleware('guest')
    ->group(function () {
        # Captha
        Route::get('captcha', [\App\Http\Controllers\Web\AuthController::class, 'captcha'])->name('captcha');
        # Admin
        Route::get('/admin', [\App\Http\Controllers\Web\AuthController::class, 'createAdmin'])->name('admin.create');
        Route::post('/admin', [\App\Http\Controllers\Web\AuthController::class, 'storeAdmin'])->name('admin.store');
        # Instructor
        Route::get('/instructor', [\App\Http\Controllers\Web\AuthController::class, 'createInstructor'])->name('instructor.create');
        Route::post('/instructor', [\App\Http\Controllers\Web\AuthController::class, 'storeInstructor'])->name('instructor.store');
        # Participant
        Route::get('/', [\App\Http\Controllers\Web\AuthController::class, 'createParticipant'])->name('participant.create');
        Route::post('/', [\App\Http\Controllers\Web\AuthController::class, 'storeParticipant'])->name('participant.store');
    });
# Sign Out
Route::post('/sign-out', [\App\Http\Controllers\Web\AuthController::class, 'logout'])
    ->name('logout')
    ->middleware('auth');

// Route::get('/', function () {
//     // Session::put('token', 'value token');
//     $value = session('token');
//     dd($value);
// });
# Home Page
Route::get('/', \App\Http\Controllers\Web\HomeController::class)->name('home');
# About Page
Route::get('/about', [\App\Http\Controllers\Web\StaticPageController::class, 'about'])->name('about');
# Contact Page
Route::get('/contact', [\App\Http\Controllers\Web\StaticPageController::class, 'contact'])->name('contact');
# Course
Route::get('/course', [\App\Http\Controllers\Web\CourseController::class, 'index'])->name('course.index');
Route::get('/course/{course:slug}', [\App\Http\Controllers\Web\CourseController::class, 'show'])->name('course.show');
Route::post('/course/{course:slug}/{schedule}', [\App\Http\Controllers\Web\CourseController::class, 'store'])->name('course.store')->middleware(IsParticipantMiddleware::class);
# Category
Route::get('/category', [\App\Http\Controllers\Web\CategoryController::class, 'index'])->name('category.index');
Route::get('/category/{category}', [\App\Http\Controllers\Web\CategoryController::class, 'show'])->name('category.show');

# Customer 
# Route::prefix('learn')

# Captcha
Route::get('captcha-url', [\App\Http\Controllers\Web\CapthaController::class, 'captcha'])->name('captcha');

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
