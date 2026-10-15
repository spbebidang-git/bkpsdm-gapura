<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Course\CourseService;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(CourseService $course)
    {
        return Inertia::render('Home/Index', [
            'courses' => $course->getFeaturedCourses(user: request()->user()),
            'bannerUrl' => get_setting_meta('home_banner')
                ? asset('assets/media/images/managements/settings/'.get_setting_meta('home_banner'))
                : asset('assets/media/images/static/banner.png'),
        ]);
    }
}
