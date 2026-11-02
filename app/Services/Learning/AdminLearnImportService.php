<?php

namespace App\Services\Learning;

use App\Jobs\ImportLearnJob;
use App\Repositories\Course\Contracts\CourseRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class AdminLearnImportService
{
    public function __construct(private readonly CourseRepositoryInterface $courses) {}

    public function indexData(string $actorId): array
    {
        $courses = $this->courses->importCourses();

        $importResults = null;
        $importCourse = null;
        if ($cached = Cache::pull('import_learn:'.$actorId)) {
            $importResults = $cached['results'] ?? null;
            $importCourse = $cached['course'] ?? null;
        }

        return compact('courses', 'importResults', 'importCourse');
    }

    public function queue(UploadedFile $file, array $data, string $actorId, bool $createMissingUsers): void
    {
        $path = $file->store('imports');

        ImportLearnJob::dispatch(
            $path,
            $data['course_id'],
            $data['finished_at'],
            $actorId,
            $createMissingUsers
        );
    }
}
