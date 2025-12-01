<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\IsParticipantMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Auth::loginUsingId(2);
Route::middleware(HandleInertiaRequests::class)->group(function () {

    # Sign In / Sign Out
    Route::get('/sign-in', [AuthController::class, 'index'])->name('sign-in.index');
    Route::post('/sign-in', [AuthController::class, 'store'])->name('sign-in.store');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

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
    Route::get('/help', [\App\Http\Controllers\Web\HelpController::class, 'index'])
        ->name('help.index');
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
});

require __DIR__ . '/admin.php';
