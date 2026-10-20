<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Middleware\AdminLocale;
use App\Http\Middleware\AuditAdminActions;
use App\Http\Middleware\IsAdminMiddleware;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
| Order follows the sidebar (resources/js/Layouts/Admin/navigation.ts):
|   1. Summary  — dashboard
|   2. Master   — category, opd, employee-status, trainer, participant, course
|   3. Results  — report, certificate, review
|   4. Management — help, notification-messages, permission, role, user, setting, log
|   5. Extras   — routes without a sidebar entry (result, course-performance, import-learn)
|
| Role access follows PermissionSeeder::ALL + syncPermissions:
|   super_admin   : all permissions
|   administrator : all except notification, permission, role, user, setting, log
|   trainer       : dashboard, category view, course view/create/update, activity, participant view, result
|   participant   : dashboard only (outside the admin panel)
| Routes without an explicit permission are restricted to non-participant roles,
| and trainers are limited to courses on their own active OPD (see IsAdminMiddleware).
*/

Route::prefix('/admin')
    ->name('admin.')
    ->middleware([AdminLocale::class, AuditAdminActions::class, IsAdminMiddleware::class])
    ->group(function () {
        // 1. Summary
        Route::get('/', DashboardController::class)->name('dashboard');

        // 2. Master
        require __DIR__ . '/admin/master.php';

        // 3. Results
        require __DIR__ . '/admin/results.php';

        // 4. Management
        require __DIR__ . '/admin/management.php';

        // 5. Extras — no sidebar entry
        require __DIR__ . '/admin/extras.php';
    });
