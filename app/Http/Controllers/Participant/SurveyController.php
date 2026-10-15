<?php

namespace App\Http\Controllers\Participant;

use App\Models\Course\Course;
use App\Models\Learning\Learn;
use App\Services\Identity\UserService;
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

    public function index(Course $course, $scheduleId)
    {
        $learn = Learn::where('participant_id', $this->participantId)->where('course_id', $course->id)->first();

        return Inertia::render('Participant/Survey/Index', [
            'course' => $course,
            'progress' => $learn->progress ?? '0 %',
            'schedule_id' => $scheduleId,
            'survey_url' => 'http://bit.ly/SurveiKepuasanPenggunaGAPURA',
            'initial_activity' =>  $course->activities->first() ? $course->activities->first()->id : 0,
        ]);
    }
}
