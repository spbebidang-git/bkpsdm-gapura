<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Course\CoursePerformanceService;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class CoursePerformanceController extends Controller
{
    public function index(CoursePerformanceService $service)
    {
        if (request()->expectsJson() && ! request()->header('X-Inertia')) {
            return DataTables::of($service->getPerformance())->addIndexColumn()->make();
        }

        return Inertia::render('Admin/CoursePerformance/Index', [
            'result' => $service->getPerformance(),
        ]);
    }
}
