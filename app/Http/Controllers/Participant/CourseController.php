<?php

namespace App\Http\Controllers\Participant;

use App\Models\Course\Course;
use App\Services\Course\CourseService;
use App\Services\Course\ScheduleUserService;
use App\Services\Learn\LearnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CourseController
{
    protected $course, $participantId, $scheduleUser, $learn;

    public function __construct(
        CourseService $courseService,
        ScheduleUserService $scheduleUserService,
        LearnService $learnService
    ) {
        $this->course = $courseService;
        $this->participantId = Auth::user()->id;
        $this->scheduleUser = $scheduleUserService;
        $this->learn = $learnService;
    }

    public function index()
    {
        try {
            return Inertia::render('Participant/Course/Index', [
                'courses' => $this->course->getByParticipant($this->participantId)
            ]);
        } catch (\Throwable $e) {
            \Log::channel('app_errors')->error('Participant CourseController@index error: ' . $e->getMessage(), [
                'user'  => $this->participantId,
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('home')
                ->with('danger', 'Terjadi kesalahan saat memuat daftar kursus. Silakan coba lagi.');
        }
    }

    public function show(Course $course)
    {
        try {
            $firstSchedule = $course->schedules->first();

            $learnModel = $this->learn->findLearn($course->id, $this->participantId);

            # tgl aktivasi , tgl selesai , nilai post test, jam pelajaran
            $data = [
                'activation_date' => $firstSchedule
                    ? $this->scheduleUser->getActivationDate($firstSchedule->id, $this->participantId)
                    : null,
                'finished_at'   => $this->learn->getFinishedAt($course->id, $this->participantId),
                'post_test_avg' => $this->learn->getPostTestAvg($course->id, $this->participantId),
                'jam_pelajaran' => $course->jam_pelajaran,
                'url_certificate' => $learnModel ? $learnModel->url_certificate : null,
            ];

            $course = $this->course->findBySlug($course->slug);

            return Inertia::render('Participant/Course/Show', [
                'course' => [
                    'id'               => $course->id,
                    'type'             => $course->type,
                    'title'            => $course->title,
                    'employee_status'  => $course->employee_status,
                    'short_description'=> $course->short_description,
                    'description'      => $course->description,
                    'teaser_video'     => $course->teaser_video,
                    'slug'             => $course->slug,
                    'quota'            => $course->quota,
                    'thumbnail'        => $course->thumbnail_src,
                    'category'         => $course->category->title,
                    'instructor'       => $course->instructor->name,
                    'total_participant'=> $course->total_participant,
                    'status'           => $course->status,
                ],
                'learn' => $data
            ]);
        } catch (\Throwable $e) {
            \Log::channel('app_errors')->error('Participant CourseController@show error: ' . $e->getMessage(), [
                'course' => $course->slug ?? null,
                'user'   => $this->participantId,
            ]);
            return redirect()->route('participant.course.index')
                ->with('danger', 'Terjadi kesalahan saat memuat halaman kursus. Silakan coba lagi.');
        }
    }
}
