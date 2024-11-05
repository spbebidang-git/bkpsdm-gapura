<?php

namespace App\Http\Controllers\Participant;

use App\Models\Course\Course;
use App\Models\Learn\Learn;
use App\Services\User\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SurveyController
{
    protected $activity, $course, $participantId, $learn, $user;

    public function __construct(UserService $userService)
    {
        $this->participantId = Auth::user()->id;
        $this->user = $userService;
    }

    public function index(Course $course)
    {
        return Inertia::render('Participant/Survey/Index', [
            'course' => $course,
            'progress' => Learn::where('participant_id', $this->participantId)->first()->progress ?? '0 %',
            'initial_activity' =>  $course->activities->first() ? $course->activities->first()->id : 0,
        ]);
    }
}
