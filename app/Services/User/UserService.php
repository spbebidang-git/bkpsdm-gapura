<?php

namespace App\Services\User;

use App\Helper\UploadMedia;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function getParticipant()
    {
        return User::with("roles")->whereHas("roles", function ($q) {
            $q->whereIn("name", ["Participant"]);
        })->orderBy('id')->get();
    }

    public function getInstructor()
    {
        return User::with("roles")->whereHas("roles", function ($q) {
            $q->whereIn("name", ["Instructor"]);
        })->orderBy('id')->get();
    }
}
