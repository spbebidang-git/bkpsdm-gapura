<?php

namespace App\Http\Controllers\Participant;

use App\Services\User\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ProfileController
{
    public function index()
    {
        return Inertia::render('Participant/Profile/Index');
    }
}
