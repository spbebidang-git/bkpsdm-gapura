<?php

use App\Http\Middleware\CheckRoleAuthMiddleware;
use App\Http\Middleware\IsAdminMiddleware;
use App\Jobs\BulkCertificateJob;
use App\Models\Learning\Learn;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

Route::prefix('/admin')
    ->name('admin.')
    ->middleware([\App\Http\Middleware\AdminLocale::class, \App\Http\Middleware\AuditAdminActions::class, IsAdminMiddleware::class])
    ->group(function () {
        # Dashboard
        Route::get('/', \App\Http\Controllers\Admin\DashboardController::class)->name('dashboard');
        # Participant
        Route::resource('participant', \App\Http\Controllers\Admin\ParticipantController::class)->except('show');
        # Instructor — deferred (nav hidden); keep routes for now
        Route::resource('instructor', \App\Http\Controllers\Admin\InstructorController::class)->except('show');

        Route::resource('trainer', \App\Http\Controllers\Admin\TrainerController::class)->except('show');
        Route::resource('opd', \App\Http\Controllers\Admin\OpdController::class)->except('show');
        Route::post('opd/sync', [\App\Http\Controllers\Admin\OpdController::class, 'sync'])->name('opd.sync');
        Route::resource('employee-status', \App\Http\Controllers\Admin\EmployeeStatusController::class)->except('show')->parameters(['employee-status' => 'employeeStatus']);
        # Category — SSO mirror read-only + sync
        Route::get('category', [\App\Http\Controllers\Admin\CategoryController::class, 'index'])->name('category.index');
        Route::post('category/sync', [\App\Http\Controllers\Admin\CategoryController::class, 'sync'])->name('category.sync');
        # Course — full management (ex-instructor) inside admin panel
        Route::get('course', [\App\Http\Controllers\Admin\CourseController::class, 'index'])->name('course.index');
        Route::prefix('course')
            ->name('course.')
            ->group(function () {
                Route::get('/create', [\App\Http\Controllers\Admin\CourseController::class, 'create'])->name('create');
                Route::post('/', [\App\Http\Controllers\Admin\CourseController::class, 'store'])->name('store');
                Route::get('/{course:slug}/overview', [\App\Http\Controllers\Admin\CourseController::class, 'overview'])->name('overview');
                Route::get('/{course:slug}/schedules', [\App\Http\Controllers\Admin\Course\ScheduleController::class, 'index'])->name('schedules.index');
                Route::post('/{course:slug}/schedules', [\App\Http\Controllers\Admin\Course\ScheduleController::class, 'store'])->name('schedules.store');
                Route::put('/{course:slug}/schedules/{schedule}', [\App\Http\Controllers\Admin\Course\ScheduleController::class, 'update'])->name('schedules.update');
                Route::delete('/{course:slug}/schedules/{schedule}', [\App\Http\Controllers\Admin\Course\ScheduleController::class, 'destroy'])->name('schedules.destroy');
                Route::get('/{course:slug}/activities', [\App\Http\Controllers\Admin\Course\ActivityController::class, 'management'])->name('activities.index');
                Route::get('/{course:slug}', [\App\Http\Controllers\Admin\CourseController::class, 'show'])->name('show');
                Route::get('/{course:slug}/edit', [\App\Http\Controllers\Admin\CourseController::class, 'edit'])->name('edit');
                Route::put('/{course:slug}', [\App\Http\Controllers\Admin\CourseController::class, 'update'])->name('update');
                Route::delete('/{course:slug}', [\App\Http\Controllers\Admin\CourseController::class, 'destroy'])->name('destroy');
                Route::post('/{course:slug}/lock', [\App\Http\Controllers\Admin\CourseController::class, 'lock'])->name('lock');

                Route::prefix('/{course:slug}/activity')
                    ->name('activity.')
                    ->middleware('course.lock')
                    ->group(function () {
                        Route::get('/', [\App\Http\Controllers\Admin\Course\ActivityController::class, 'index'])->name('activity');
                        Route::delete('/{activityId}', [\App\Http\Controllers\Admin\Course\ActivityController::class, 'destroy'])->name('destroy');
                        Route::resource('pre-test', \App\Http\Controllers\Admin\Course\Question\PreTestController::class);
                        Route::resource('post-test', \App\Http\Controllers\Admin\Course\Question\PostTestController::class);
                        Route::get('{activity}/import-question', [\App\Http\Controllers\Admin\Course\Question\ImportQuestionController::class, 'index'])->name('import-question.index');
                        Route::get('{activity}/import-question/download', [\App\Http\Controllers\Admin\Course\Question\ImportQuestionController::class, 'download'])->name('import-question.download');
                        Route::post('{activity}/import-question', [\App\Http\Controllers\Admin\Course\Question\ImportQuestionController::class, 'store'])->name('import-question.store');
                        Route::prefix('variant/{activityId}')
                            ->name('variant.')
                            ->group(function () {
                                Route::get('/', [\App\Http\Controllers\Admin\Course\Question\VariantController::class, 'index'])->name('index');
                                Route::get('/create', [\App\Http\Controllers\Admin\Course\Question\VariantController::class, 'create'])->name('create');
                                Route::post('/', [\App\Http\Controllers\Admin\Course\Question\VariantController::class, 'store'])->name('store');
                                Route::get('/{variant}/edit', [\App\Http\Controllers\Admin\Course\Question\VariantController::class, 'edit'])->name('edit');
                                Route::put('/{variant}', [\App\Http\Controllers\Admin\Course\Question\VariantController::class, 'update'])->name('update');
                                Route::delete('/{variant}', [\App\Http\Controllers\Admin\Course\Question\VariantController::class, 'destroy'])->name('destroy');
                                Route::resource('/{variant}/question-item', \App\Http\Controllers\Admin\Course\Question\QuestionItemController::class);
                            });
                        Route::get('task/{activityId}/result', [\App\Http\Controllers\Admin\Course\Task\TaskController::class, 'result'])->name('result');
                        Route::resource('task', \App\Http\Controllers\Admin\Course\Task\TaskController::class);
                        Route::post('/{activityId}/meeting/end-meeting/', [\App\Http\Controllers\Admin\Course\Interactive\OnlineMeetingController::class, 'endMeeting'])->name('meeting.end-meeting');
                        Route::resource('meeting', \App\Http\Controllers\Admin\Course\Interactive\OnlineMeetingController::class);
                        Route::resource('video', \App\Http\Controllers\Admin\Course\Interactive\PresentationVideoController::class);
                        Route::resource('literature', \App\Http\Controllers\Admin\Course\Literature\LiteratureController::class);
                    });
            });
        # Review
        Route::resource('review', \App\Http\Controllers\Admin\ReviewController::class)
            ->only(['index', 'destroy']);
        # Help
        Route::resource('help', \App\Http\Controllers\Admin\HelpController::class)->except('show')->names('help');
        # Result
        Route::get('/result', [\App\Http\Controllers\Admin\ResultController::class, 'index'])->name('result.index');
        Route::get('/result/post-test/{course:slug}', [\App\Http\Controllers\Admin\ResultController::class, 'postTest'])->name('result.post-test');
        Route::get('/result/{course:slug}', [\App\Http\Controllers\Admin\ResultController::class, 'show'])->name('result.show');
        # Course Performance
        Route::get('course-performance', [\App\Http\Controllers\Admin\CoursePerformanceController::class, 'index'])
            ->name('course-performance.index');

        # Import Learn
        Route::get('import-learn', [\App\Http\Controllers\Admin\ImportLearnController::class, 'index'])->name('import-learn.index');
        Route::post('import-learn', [\App\Http\Controllers\Admin\ImportLearnController::class, 'store'])->name('import-learn.store');
        Route::get('import-learn/template', [\App\Http\Controllers\Admin\ImportLearnController::class, 'downloadTemplate'])->name('import-learn.template');

        # Management 
        Route::prefix('management')
            ->name('management.')
            ->group(function () {
                # Role
                Route::resource('role', \App\Http\Controllers\Admin\Management\RoleController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
                # Permission
                Route::resource('permission', \App\Http\Controllers\Admin\Management\PermissionController::class)->except('show');
                # Setting
                Route::resource('setting', \App\Http\Controllers\Admin\Management\SettingController::class)->only('index', 'store');
                # Notification messages
                Route::get('notification-messages', [\App\Http\Controllers\Admin\Management\NotificationMessageController::class, 'index'])->name('notification-messages.index');
                Route::put('notification-messages', [\App\Http\Controllers\Admin\Management\NotificationMessageController::class, 'update'])->name('notification-messages.update');
                # User
                Route::resource('user', \App\Http\Controllers\Admin\Management\UserController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
                # Log & backup
                Route::get('log', [\App\Http\Controllers\Admin\Management\LogController::class, 'index'])->name('log.index');
                Route::get('log/download', [\App\Http\Controllers\Admin\Management\LogController::class, 'download'])->name('log.download');
            });

        Route::get('report', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('report');
        Route::get('report/data', [\App\Http\Controllers\Admin\ReportController::class, 'data'])->name('report.data');
        Route::get('report/groups', [\App\Http\Controllers\Admin\ReportController::class, 'groups'])->name('report.groups');

        Route::get('/certificate', [App\Http\Controllers\Admin\CertificateController::class, 'index'])->name('certificate.index');
        Route::post('/certificate/sync-push', [App\Http\Controllers\Admin\CertificateController::class, 'syncPush'])->name('certificate.sync-push');
        Route::post('/certificate/sync-pull', [App\Http\Controllers\Admin\CertificateController::class, 'syncPull'])->name('certificate.sync-pull');

        Route::get('bulk-certificate/{courseId}', function ($courseId) {
            $results = DB::table('users')
                ->select([
                    'courses.title',
                    'courses.category_id',
                    'users.id as user_id',
                    'nip_meta.value as nip',
                    'fc_kduker_meta.value as fc_kduker_meta',
                    'fc_kdsatker_meta.value as fc_kdsatker_meta',
                    'fc_kdsubsatker_meta.value as fc_kdsubsatker_meta',
                    DB::raw("DATE_FORMAT(learns.created_at, '%Y-%m-%d') as created_at"),
                    DB::raw("DATE_FORMAT(learns.finished_at, '%Y-%m-%d') as finished_at")
                ])
                ->join('user_meta as nip_meta', function ($join) {
                    $join->on('nip_meta.user_id', '=', 'users.id')
                        ->where('nip_meta.key', '=', 'nip');
                })
                ->leftJoin('user_meta as fc_kduker_meta', function ($join) {
                    $join->on('fc_kduker_meta.user_id', '=', 'users.id')
                        ->where('fc_kduker_meta.key', '=', 'fc_kduker');
                })
                ->leftJoin('user_meta as fc_kdsatker_meta', function ($join) {
                    $join->on('fc_kdsatker_meta.user_id', '=', 'users.id')
                        ->where('fc_kdsatker_meta.key', '=', 'fc_kdsatker');
                })
                ->leftJoin('user_meta as fc_kdsubsatker_meta', function ($join) {
                    $join->on('fc_kdsubsatker_meta.user_id', '=', 'users.id')
                        ->where('fc_kdsubsatker_meta.key', '=', 'fc_kdsubsatker');
                })
                ->join('reviews', 'reviews.user_id', '=', 'users.id')
                ->join('learns', 'learns.participant_id', '=', 'users.id')
                ->join('courses', 'learns.course_id', '=', 'courses.id')
                ->whereNotNull('learns.finished_at')
                ->where('courses.id', $courseId)
                ->groupBy('nip_meta.value')
                ->get();

            // dd($results);
            $token = Session::get('token');
            // dd($token);
            foreach ($results as $item) {
                BulkCertificateJob::dispatch($item, $token);
            }

            dd('oke');
        });
    });
