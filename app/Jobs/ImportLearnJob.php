<?php

namespace App\Jobs;

use App\Imports\LearnBulkImport;
use App\Models\Course\Course;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ImportLearnJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;

    public $tries = 1;

    public function __construct(
        protected string $filePath,
        protected int|string $courseId,
        protected string $finishedAt,
        protected int|string $adminId,
    ) {}

    public function handle(): void
    {
        $import = new LearnBulkImport($this->courseId, $this->finishedAt);

        Excel::import($import, Storage::path($this->filePath));

        $results = $import->getResults();
        $course = Course::find($this->courseId);

        Cache::put('import_learn:' . $this->adminId, [
            'results' => $results,
            'course' => $course?->title ?? (string) $this->courseId,
        ], now()->addHours(6));

        Log::info('ImportLearnJob selesai', [
            'course_id' => $this->courseId,
            'admin_id' => $this->adminId,
            'results' => $results,
        ]);

        Storage::delete($this->filePath);
    }

    public function failed(?\Throwable $e): void
    {
        Log::error('ImportLearnJob gagal', [
            'course_id' => $this->courseId,
            'admin_id' => $this->adminId,
            'error' => $e?->getMessage(),
        ]);

        Cache::put('import_learn:' . $this->adminId, [
            'results' => [
                'success' => 0,
                'skipped' => 0,
                'failed' => 0,
                'errors' => ['Import gagal: ' . ($e?->getMessage() ?? 'unknown')],
            ],
            'course' => (string) $this->courseId,
        ], now()->addHours(6));

        Storage::delete($this->filePath);
    }
}
