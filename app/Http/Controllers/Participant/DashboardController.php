<?php

namespace App\Http\Controllers\Participant;

use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController
{
    public function __invoke(Request $request)
    {
        return Inertia::render('Participant/Dashboard');
    }
}
