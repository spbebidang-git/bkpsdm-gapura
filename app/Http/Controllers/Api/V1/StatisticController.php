<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StatisticYearRequest;
use App\Services\Reporting\PlatformStatisticService;

class StatisticController extends Controller
{
    public function __construct(private readonly PlatformStatisticService $statistics) {}

    public function overview()
    {
        return response()->json([
            'success' => true,
            'message' => 'Statistik keseluruhan berhasil diambil.',
            'data' => [$this->statistics->overview()],
        ]);
    }

    public function yearly(StatisticYearRequest $request)
    {
        $year = (int) ($request->validated('year') ?? now()->year);

        return response()->json([
            'success' => true,
            'message' => "Statistik tahun {$year} berhasil diambil.",
            'data' => $this->statistics->yearly($year),
        ]);
    }

    public function employeeStatus()
    {
        return response()->json([
            'success' => true,
            'message' => 'Distribusi per status kepegawaian berhasil diambil.',
            'data' => $this->statistics->employeeStatus(),
        ]);
    }
}
