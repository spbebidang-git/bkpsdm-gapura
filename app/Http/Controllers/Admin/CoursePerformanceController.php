<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Course\CoursePerformanceService;
use Inertia\Inertia;

class CoursePerformanceController extends Controller
{
    public function index(CoursePerformanceService $service)
    {
        return Inertia::render('Admin/CoursePerformance/Index', [
            'result' => $service->getPerformance(),
        ]);
    }
}
