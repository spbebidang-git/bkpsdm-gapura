<?php

namespace App\Http\Controllers\Participant;

use App\Models\Course\Course;
use App\Services\Course\CourseService;
use App\Services\Course\ScheduleUserService;
use App\Services\Learning\LearnService;
use App\Services\Learning\ParticipantEnrollmentService;
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
                ->with('danger', 'Terjadi kesalahan saat memuat daftar kelas. Silakan coba lagi.');
        }
    }

    public function show(Course $course)
    {
        try {
            $firstSchedule = $course->schedules()
                ->whereHas('participants', fn ($query) => $query->where('users.id', $this->participantId))
                ->first();
            $firstActivity = $course->activities()->orderBy('sort_order')->orderBy('id')->first();
            $entryUrl = null;
            $entryMessage = null;
            if (! $firstSchedule) {
                $entryMessage = 'Aktifkan kelas terlebih dahulu melalui halaman informasi kelas.';
            } elseif (! $firstActivity) {
                $entryMessage = 'Materi pelatihan belum tersedia.';
            } else {
                $enrollmentService = app(ParticipantEnrollmentService::class);
                $enrollment = $enrollmentService->forRequest((string) $this->participantId, $course, $firstSchedule);
                if (config('participant.enrollment_access_enforced', true) && (! $enrollment || ! $enrollmentService->canRead($enrollment))) {
                    $entryMessage = ! $enrollment ? 'Aktivasi kelas belum tercatat.'
                        : (now()->lt($enrollment->access_starts_at) ? 'Pelatihan belum dimulai sesuai jadwal akses.' : 'Masa akses pelatihan telah berakhir.');
                } else {
                    $entryUrl = route('participant.learn.instruction.index', [$course->slug, $firstSchedule->id, $firstActivity->id]);
                }
            }

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
                'entry_url' => $entryUrl,
                'entry_message' => $entryMessage,
            ];

            $course = $this->course->findBySlug($course->slug, Auth::user());
            abort_unless($course, 404);
            $course->loadMissing(['trainers.user.metas', 'activities', 'reviews.user']);

            return Inertia::render('Participant/Course/Show', [
                'course' => [
                    'id'               => $course->id,
                    'type'             => $course->type,
                    'title'            => $course->title,
                    'employee_status'  => $course->employeeStatus->pluck('title')->map(fn($t) => trim($t))->join(', '),
                    'short_description'=> $course->short_description,
                    'description'      => $course->description,
                    'teaser_video'     => $course->teaser_video,
                    'slug'             => $course->slug,
                    'quota'            => $course->quota,
                    'thumbnail'        => $course->thumbnail_src,
                    'category'         => $course->category->title,
                    
                    'total_participant'=> $course->total_participant,
                    'status'           => $course->status,
                    'audience_scope'   => $course->opd_id ? 'opd' : 'all',
                    'opd_id'           => $course->opd_id,
                    'opd_name'         => $course->opd?->name,
                    'access_hours'     => $course->default_access_hours,
                    'schedule'         => $firstSchedule ? [
                        'date' => $course->type === 'schedule' ? $firstSchedule->date : null,
                        'time' => $course->type === 'schedule' ? $firstSchedule->time : null,
                        'quota' => $firstSchedule->quota,
                    ] : null,
                    'trainers' => $course->trainers->map(fn ($trainer) => [
                        'id' => $trainer->id, 'name' => $trainer->name,
                        'position' => $trainer->jabatan, 'photo' => $trainer->photo_src,
                    ])->values(),
                    'activities' => $course->activities->sortBy(fn ($activity) => sprintf('%010d-%s', $activity->sort_order, $activity->id))->map(fn ($activity) => [
                        'id' => $activity->id, 'title' => $activity->title, 'type' => $activity->type,
                    ])->values(),
                    'rating' => $course->getRating(),
                    'reviews' => $course->reviews->sortByDesc('created_at')->take(12)->map(fn ($review) => [
                        'id' => $review->id, 'name' => $review->user?->name ?? 'Peserta GAPURA',
                        'star' => $review->star, 'description' => $review->description,
                        'date' => $review->created_at?->format('Y-m-d'),
                    ])->values(),
                ],
                'learn' => $data
            ]);
        } catch (\Throwable $e) {
            \Log::channel('app_errors')->error('Participant CourseController@show error: ' . $e->getMessage(), [
                'course' => $course->slug ?? null,
                'user'   => $this->participantId,
            ]);
            return redirect()->route('participant.course.index')
                ->with('danger', 'Terjadi kesalahan saat memuat halaman kelas. Silakan coba lagi.');
        }
    }
}
