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
                if ($row->learnItems->where('type', 'pre-test')->first()) {
                    $responsePre = unserialize($row->learnItems->where('type', 'pre-test')->first()->response);
                    // dd($responsePre[0]['score']);
                    foreach ($responsePre as $item) {
                        $pre = $pre + $item['score'];
                    }

                    $preCorrect = count(search($responsePre, 'is_correct', true));

                    $preWrong = count(search($responsePre, 'is_correct', false));

                    $preScore = $responsePre[0]['score'];
                }

                $post = 0;
                if ($row->learnItems->where('type', 'post-test')->first()) {
                    $responsePost = unserialize($row->learnItems->where('type', 'post-test')->first()->response);
                    foreach ($responsePost as $item) {
                        $post = $pre + $item['score'];
                    }

                    $postCorrect = count(search($responsePost, 'is_correct', true));

                    $postWrong = count(search($responsePost, 'is_correct', false));

                    $postScore = $responsePost[0]['score'];
                }

                return [
                    'name' => $row->participant->name,
                    'pre_score_total' => $pre ?? 'kosong',
                    'pre_correct' => $preCorrect ?? 'kosong',
                    'pre_wrong' => $preWrong ?? 'kosong',
                    'pre_score' => $preScore ?? 'kosong',
                    'post_score_total' => $post ?? 'kosong',
                    'post_correct' => $postCorrect ?? 'kosong',
                    'post_wrong' => $postWrong ?? 'kosong',
                    'post_score' => $postScore ?? 'kosong',
                    'finished_at' => $row->finished_at,
                ];
            });

            foreach ($a as $item) {
                echo '<table>
                    <tr>
                        <td>' . $item['name'] . '</td>
                        <td>' . $item['pre_score_total'] . '</td>
                        <td>' . $item['pre_correct'] . '</td>
                        <td>' . $item['pre_wrong'] . '</td>
                        <td>' . $item['pre_score'] . '</td>

                        <td>' . $item['post_score_total'] . '</td>
                        <td>' . $item['post_correct'] . '</td>
                        <td>' . $item['post_wrong'] . '</td>
                        <td>' . $item['post_score'] . '</td>

                        <td>' . $item['finished_at'] . '</td>
                    </tr>
                </table>';
            }
        });
    });
