<?php

use App\Http\Middleware\CheckRoleAuthMiddleware;
use App\Http\Middleware\IsAdminMiddleware;
use App\Models\Learn\Learn;
use Illuminate\Support\Facades\Route;

Route::prefix('/admin')
    ->name('admin.')
    ->middleware([IsAdminMiddleware::class])
    ->group(function () {
        # Dashboard
        Route::get('/', \App\Http\Controllers\Admin\DashboardController::class)->name('dashboard');
        # Participant 
        Route::resource('participant', \App\Http\Controllers\Admin\ParticipantController::class);
        # Instructor 
        Route::resource('instructor', \App\Http\Controllers\Admin\InstructorController::class)->except('show');
        # Category 
        Route::resource('category', \App\Http\Controllers\Admin\CategoryController::class);
        # Course 
        Route::resource('course', \App\Http\Controllers\Admin\CourseController::class)->only('index', 'show');
        # Review
        Route::resource('review', \App\Http\Controllers\Admin\ReviewController::class);

        # Management 
        Route::prefix('management')
            ->name('management.')
            ->group(function () {
                # Role
                Route::resource('role', \App\Http\Controllers\Admin\Management\RoleController::class)->only('index');
                # Permission
                Route::resource('permission', \App\Http\Controllers\Admin\Management\PermissionController::class)->except('show');
                # Setting
                Route::resource('setting', \App\Http\Controllers\Admin\Management\SettingController::class)->only('index', 'store');
                # User
                Route::resource('user', \App\Http\Controllers\Admin\Management\UserController::class)->except('show');
            });

        Route::get('report', function () {
            $query = Learn::with('participant', 'learnItems')->get();

            $a = $query->map(function ($row) {
                // dd(unserialize($row->learnItems->where('type', 'pre-test')->first()->response));
                $pre = 0;
                if ($row->learnItems->where('type', 'pre-test')->first()->response) {
                    foreach (unserialize($row->learnItems->where('type', 'pre-test')->first()->response) as $item) {
                        $pre = $pre + $item['score'];
                    }
                } else {
                    $pre = null;
                }

                $post = 0;
                if ($row->learnItems->where('type', 'post-test')->first()) {
                    foreach (unserialize($row->learnItems->where('type', 'post-test')->first()->response) as $item) {
                        $post = $pre + $item['score'];
                    }
                } else {
                    $post = null;
                }

                return [
                    'name' => $row->participant->name,
                    'pre_score' => $pre,
                    'post_score' => $post,
                ];
            });

            foreach ($a as $item) {
                echo '<table>
                    <tr>
                        <td>' . $item['name'] . '</td>
                        <td>' . $item['pre_score'] . '</td>
                        <td>' . $item['post_score'] . '</td>
                    </tr>
                </table>';
            }
        });
    });
