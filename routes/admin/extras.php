<?php

use App\Http\Controllers\Admin\CoursePerformanceController;
use App\Http\Controllers\Admin\ImportLearnController;
use App\Http\Controllers\Admin\ResultController;
use App\Jobs\BulkCertificateJob;
use Illuminate\Support\Facades\Route;

// 5. Extras — additional routes

// Result detail. Access: super_admin, administrator, trainer (`result.view`)
Route::controller(ResultController::class)->prefix('result')->name('result.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::get('/post-test/{course:slug}', 'postTest')->name('post-test');
    Route::get('/{course:slug}', 'show')->name('show');
});

// Course performance. Access: super_admin, administrator, trainer (`result.view`)
Route::get('course-performance', [CoursePerformanceController::class, 'index'])->name('course-performance.index');

// Import learn. Access: super_admin, administrator (`import.manage`)
Route::controller(ImportLearnController::class)->prefix('import-learn')->name('import-learn.')->group(function () {
    Route::get('/', 'index')->name('index');
    Route::post('/', 'store')->name('store');
    Route::get('/template', 'downloadTemplate')->name('template');
});

// Bulk certificate helper (unnamed). Access: super_admin, administrator (`certificate.manage`)
Route::get('bulk-certificate/{courseId}', function ($courseId) {
    BulkCertificateJob::dispatch($courseId);

    return back()->with('success', 'Pengiriman sertifikat dijadwalkan.');
});
