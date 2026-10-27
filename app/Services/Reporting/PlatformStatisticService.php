<?php

namespace App\Services\Reporting;

use App\Repositories\Reporting\Contracts\PlatformStatisticRepositoryInterface;
use Illuminate\Support\Str;

class PlatformStatisticService
{
    public function __construct(private readonly PlatformStatisticRepositoryInterface $stats) {}

    public function overview(): array
    {
        $data = $this->stats->overview();
        $rate = $data['total_enrollments'] > 0
            ? round($data['total_completed'] / $data['total_enrollments'] * 100, 2) : 0;
        return [
            'custom_unique_id' => Str::random(4), 'data_date' => now()->format('Y-m-d H:i:s'),
            ...$data, 'completion_rate' => $rate.'%',
            'average_rating' => round($data['average_rating'], 2),
        ];
    }

    public function yearly(int $year): array
    {
        $data = $this->stats->yearly($year);
        $rate = $data['total_enrollments'] > 0
            ? round($data['total_completions'] / $data['total_enrollments'] * 100, 2) : 0;
        return [
            'custom_unique_id' => Str::random(4), 'data_date' => now()->format('Y-m-d H:i:s'),
            'year' => $year, ...$data, 'completion_rate' => $rate.'%',
        ];
    }

    public function employeeStatus(): array
    {
        return array_map(fn ($row) => [
            'custom_unique_id' => Str::random(4), 'data_date' => now()->format('Y-m-d H:i:s'), ...$row,
        ], $this->stats->employeeStatus());
    }
}
