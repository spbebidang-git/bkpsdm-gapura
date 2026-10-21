<?php

use App\Http\Controllers\Admin\HelpController;
use App\Http\Controllers\Admin\Management\LogController;
use App\Http\Controllers\Admin\Management\NotificationMessageController;
use App\Http\Controllers\Admin\Management\PermissionController;
use App\Http\Controllers\Admin\Management\RoleController;
use App\Http\Controllers\Admin\Management\SettingController;
use App\Http\Controllers\Admin\Management\UserController;
use Illuminate\Support\Facades\Route;

// 4. Management

// Help — kept outside the `management` prefix so names stay `admin.help.*`.
// Access: super_admin, administrator (`help.manage`)
Route::resource('help', HelpController::class)->except('show')->names('help');

Route::prefix('management')
    ->name('management.')
    ->group(function () {
        // Notification messages. Access: super_admin (`notification-template.manage`)
        Route::controller(NotificationMessageController::class)->prefix('notification-messages')->name('notification-messages.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::put('/', 'update')->name('update');
            Route::post('/test', 'test')->name('test');
        });

        // Permission. Access: super_admin (`permission.manage`)
        Route::resource('permission', PermissionController::class)->except('show');

        // Role. Access: super_admin (`role.manage`)
        Route::resource('role', RoleController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

        // User. Access: super_admin (`user.manage`)
        Route::resource('user', UserController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

        // Setting. Access: super_admin (`setting.manage`)
        Route::resource('setting', SettingController::class)->only('index', 'store');

        // Log & backup. Access: super_admin (`log.view`)
        Route::controller(LogController::class)->prefix('log')->name('log.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/download', 'download')->name('download');
        });
    });
