<?php

namespace App\Imports;

use App\Services\Learning\LearnBulkImportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LearnBulkImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    private array $results = [];

    public function __construct(
        private readonly string $courseId,
        private readonly string $finishedAt,
        private readonly bool $createMissingUsers = false,
    ) {}

    public function collection(Collection $rows): void
    {
        $this->results = app(LearnBulkImportService::class)->run(
            $rows, $this->courseId, $this->finishedAt, $this->createMissingUsers,
        );
    }

    public function getResults(): array
    {
        return $this->results;
    }
}
