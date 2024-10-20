<?php

namespace App\Http\Controllers\Participant;

use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController
{
    public function index()
    {
        return Inertia::render('Participant/Profile/Index');
    }
}
