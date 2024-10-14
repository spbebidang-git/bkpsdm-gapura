<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use Inertia\Inertia;

class StaticPageController
{
    public function about()
    {
        return Inertia::render('StaticPage/About');
    }

    public function contact()
    {
        return Inertia::render('StaticPage/Contact');
    }
}
