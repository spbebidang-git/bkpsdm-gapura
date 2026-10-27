<?php

namespace App\Http\Controllers\Participant;

use App\Models\Course\Course;
use App\Services\Course\CourseService;
use App\Services\Learning\LearnService;
use App\Services\Learning\ParticipantEnrollmentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CourseController
{
    protected $course, $participantId, $learn;

    public function __construct(
        CourseService $courseService,
        LearnService $learnService
    ) {
        $this->course = $courseService;
        $this->participantId = Auth::user()->id;
        $this->learn = $learnService;
    }

    public function index()
    {
        try {
            app(ParticipantEnrollmentService::class)->archiveExpired((string) $this->participantId);
            return Inertia::render('Participant/Course/Index', [
                'courses' => $this->course->getByParticipant($this->participantId)
            ]);
        } catch (\Throwable $e) {
            \Log::error('Daftar kelas pengguna gagal dimuat.', ['event' => 'participant.course.list_failed',
                'actor_id'  => $this->participantId,
                'exception' => $e,
            ]);
            return redirect()->route('home')
                ->with('danger', 'Terjadi kesalahan saat memuat daftar kelas. Silakan coba lagi.');
        }
    }

    public function show(Course $course)
    {
        abort_unless($this->course->isVisible($course, request()->user()), 404);
        try {
            $enrollmentService = app(ParticipantEnrollmentService::class);
            $enrollmentService->archiveExpired((string) $this->participantId, (string) $course->id);
            $firstSchedule = $this->course->participantSchedule($course, $this->participantId);
            $orderedActivities = $this->course->orderedActivities($course);
            $firstActivity = $orderedActivities->first();
            $learnModel = $this->learn->findLearn($course->id, $this->participantId);
            $enrollment = $firstSchedule
                ? $enrollmentService->forRequest((string) $this->participantId, $course, $firstSchedule)
                : null;
            $entryUrl = null;
            $entryMessage = null;
            if (! $firstSchedule) {
                $entryMessage = 'Aktifkan kelas terlebih dahulu melalui halaman informasi kelas.';
            } elseif (! $firstActivity) {
                $entryMessage = 'Materi pelatihan belum tersedia.';
            } else {
                if (config('participant.enrollment_access_enforced', true) && (! $enrollment || ! $enrollmentService->canRead($enrollment))) {
                    $wasArchived = $enrollmentService->hasArchived((string) $this->participantId, (string) $course->id);
                    $entryMessage = ! $enrollment ? ($wasArchived
                        ? 'Masa akses berakhir. Progres lama telah diarsipkan. Aktifkan ulang kelas untuk memulai dari awal.'
                        : 'Aktivasi kelas belum tercatat.')
                        : (now()->lt($enrollment->access_starts_at) ? 'Pelatihan belum dimulai sesuai jadwal akses.' : 'Masa akses pelatihan telah berakhir.');
                } else {
                    $items = $learnModel?->learnItems()->get()->keyBy('activity_id');
                    foreach ($orderedActivities as $candidate) {
                        $item = $items?->get($candidate->id);
                        if (! $item) {
                            $entryUrl = route('participant.learn.instruction.index', [$course->slug, $firstSchedule->id, $candidate->id]);
                            break;
                        }
                        if ($item->status === 'in_progress') {
                            $entryUrl = route('participant.learn.index', [$course->slug, $firstSchedule->id, $candidate->id]);
                            break;
                        }
                        if ($item->status === 'done' && ! $item->locked_at) {
                            $entryUrl = route('participant.learn.result.index', [$course->slug, $firstSchedule->id, $candidate->id]);
                            break;
                        }
                    }
                    if (! $entryUrl && $learnModel?->finished_at) {
                        $entryMessage = 'Seluruh kegiatan kelas sudah selesai.';
                    } elseif (! $entryUrl) {
                        $entryUrl = route('participant.rating.index', [$course->slug, $firstSchedule->id]);
                    }
                }
            }

            # tgl aktivasi , tgl selesai , nilai post test, jam pelajaran
            $data = [
                'activation_date' => isset($enrollment) && $enrollment?->enrolled_at
                    ? $enrollment->enrolled_at->format('Y-m-d')
                    : null,
                'finished_at'   => $this->learn->getFinishedAt($course->id, $this->participantId),
                'post_test_avg' => $this->learn->getPostTestAvg($course->id, $this->participantId),
                'jam_pelajaran' => $course->jam_pelajaran,
                'url_certificate' => $learnModel ? $learnModel->url_certificate : null,
                'entry_url' => $entryUrl,
                'entry_message' => $entryMessage,
                'needs_activation' => (bool) ($firstSchedule && ! $enrollment && $enrollmentService->hasArchived((string) $this->participantId, (string) $course->id)),
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
                    ...$course->audienceData(),
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
                        'id' => $review->id, 'name' => $review->user?->name ?? 'Pengguna GAPURA',
                        'star' => $review->star, 'description' => $review->description,
                        'date' => $review->created_at?->format('Y-m-d'),
                    ])->values(),
                ],
                'learn' => $data
            ]);
        } catch (\Throwable $e) {
            \Log::error('Detail kelas pengguna gagal dimuat.', ['event' => 'participant.course.show_failed',
                'exception' => $e,
                'course_id' => $course->id,
                'actor_id'   => $this->participantId,
            ]);
            return redirect()->route('participant.course.index')
                ->with('danger', 'Terjadi kesalahan saat memuat halaman kelas. Silakan coba lagi.');
        }
    }

    public function reactivate(Course $course)
    {
        $participantId = (string) Auth::id();
        abort_unless(app(\App\Services\Course\CourseAudience::class)->canEnroll($course, request()->user()), 403);
        $enrollmentService = app(ParticipantEnrollmentService::class);
        $enrollmentService->reactivate($course, $participantId);

        return to_route('participant.course.show', $course->slug)
            ->with('success', 'Kelas berhasil diaktifkan ulang. Silakan mulai belajar dari awal.');
    }

    public function reset(Course $course)
    {
        $participantId = (string) Auth::id();
        app(ParticipantEnrollmentService::class)->reset($course, $participantId);

        return to_route('participant.course.show', $course->slug)->with('success', 'Progres lama diarsipkan. Aktifkan kembali kelas untuk memulai dari kegiatan pertama.');
    }
}
