<?php

use App\Http\Middleware\IsInstructorMiddleware;
use Illuminate\Support\Facades\Route;

/**
 * Legacy instructor URLs → admin course panel.
 * Full course management lives under /admin/course.
 */
Route::prefix('/instructor')
    ->middleware([IsInstructorMiddleware::class])
    ->group(function () {
        Route::redirect('/', '/admin/course');
        Route::redirect('/profile', '/admin/course');
        Route::any('/course/{path?}', function (?string $path = null) {
            $target = '/admin/course'.($path ? '/'.$path : '');
            return redirect($target);
        })->where('path', '.*');
    });
