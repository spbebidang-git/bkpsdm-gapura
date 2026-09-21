<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Course\CourseService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, CourseService $course)
    {
        return Inertia::render('Home/Index', [
            'courses' => $course->getRandomCourse(),
        ]);
    }
}
