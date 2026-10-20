<?php

use App\Http\Controllers\Admin\CertificateController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\ReviewController;
use Illuminate\Support\Facades\Route;

// 3. Results

// Report. Access: super_admin, administrator, trainer (`result.view`)
Route::controller(ReportController::class)->prefix('report')->group(function () {
    Route::get('/', 'index')->name('report');
    Route::get('/data', 'data')->name('report.data');
    Route::get('/groups', 'groups')->name('report.groups');
});

// Certificate. Access: super_admin, administrator (`certificate.manage`)
Route::controller(CertificateController::class)->prefix('certificate')->name('certificate.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/export', 'export')->name('export');
    Route::post('/sync-push', 'syncPush')->name('sync-push');
    Route::post('/sync-pull', 'syncPull')->name('sync-pull');
});

// Review. Access: super_admin, administrator (`review.manage`)
Route::resource('review', ReviewController::class)->only(['index', 'destroy']);
