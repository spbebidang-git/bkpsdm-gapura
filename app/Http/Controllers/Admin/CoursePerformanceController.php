<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Services\Course\CoursePerformanceService;

class CoursePerformanceController extends Controller
{
    public function index(CoursePerformanceService $service)
    {
        $result = $service->getPerformance();
        return view('admin.course-performance.index', compact('result'));
    }
}
