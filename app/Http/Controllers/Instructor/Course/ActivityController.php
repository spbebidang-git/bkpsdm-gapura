<?php

namespace App\Http\Controllers\Instructor\Course;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityController
{
    public function __invoke(Request $request, $slug)
    {
        return Inertia::render('Instructor/Course/Activity', [
            'slug' => $slug
        ]);
    }
}
