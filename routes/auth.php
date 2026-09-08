<?php

use App\Http\Controllers\Auth\PasswordController;
use Illuminate\Support\Facades\Route;

// Account creation, password reset, email verification, and the legacy local
// login endpoint are intentionally unavailable. Participant identity is owned
// by Prestige; administrator accounts are managed by an existing Admin.
Route::middleware('auth')->group(function () {
    Route::put('password', [PasswordController::class, 'update'])->name('password.update');
});
