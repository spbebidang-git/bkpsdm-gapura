<?php

namespace App\Http\Controllers\Participant;

use App\Services\Course\CourseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CourseController
{
    protected $course, $participantId;

    public function __construct(CourseService $courseService)
    {
        $this->course = $courseService;
        $this->participantId = Auth::user()->id;
    }

    public function index()
    {
        return Inertia::render('Participant/Course/Index', [
            'courses' => $this->course->getByParticipant($this->participantId)
        ]);
    }

    public function show()
    {
        return Inertia::render('Participant/Course/Show');
    }
}
