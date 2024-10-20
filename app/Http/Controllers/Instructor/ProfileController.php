<?php

namespace App\Http\Controllers\Instructor;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ProfileController
{
    public function index()
    {
        return Inertia::render('Instructor/Profile/Index', [
            'instructor' => [
                'profile_picture' => Auth::user()->profile_picture,
                'name' => Auth::user()->name,
                'email' => Auth::user()->email,
            ]
        ]);
    }
}
