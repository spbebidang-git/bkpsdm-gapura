<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Course\Course;
use App\Services\Learning\ParticipantEnrollmentService;
use App\Models\Course\Schedule;
use App\Models\Learning\Learn;
use App\Services\Course\CourseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CourseController extends Controller
{
    protected $course, $participantId;

    public function __construct(CourseService $courseService)
    {
        $this->course = $courseService;
        $this->participantId = Auth::user()->id ?? null;
    }

    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'visibility' => ['nullable', Rule::in(['public', 'private'])],
            'employee_status' => ['nullable', 'string', Rule::in(['CPNS', 'PNS', 'PPPK', 'PPPK Paruh Waktu'])],
            'category' => ['nullable', 'string', 'exists:categories,id'],
        ]);

        return Inertia::render('Course/Index', [
            'courses' => $this->course->getAllCourse($filters, $request->user()),
            'categories' => \App\Models\Course\Category::query()
                ->orderBy('title')
                ->get(['id', 'title']),
            'filters' => $filters,
        ]);
    }

    public function show($course)
    {
        $course = $this->course->findBySlug($course, request()->user());
        abort_unless($course, 404);
        if ($this->participantId) {
            app(ParticipantEnrollmentService::class)->archiveExpired((string) $this->participantId, (string) $course->id);
        }
        $course->loadMissing(['trainers.user.metas']);
        $reviews = $course->reviews ? $course->reviews->load('user') : [];

        return Inertia::render('Course/Show', [
            'course' => [
                'id' => $course->id,
                'type' => $course->type,
                'title' => $course->title,
                'employee_status' => $course->employeeStatus->pluck('title')->map(fn($t) => trim($t))->join(', '),
                'short_description' => $course->short_description,
                'description' => $course->description,
                'teaser_video' => $course->teaser_video,
                'slug' => $course->slug,
                'quota' => $course->quota,
                'thumbnail' => $course->thumbnail_src,
                'category' => $course->category->title,
                'total_participant' => $course->total_participant,
                'total_passed' => $course->total_passed,
                'status' => $course->status,
                ...$course->audienceData(),
            ],
            'schedules' => $course->schedules
                ->filter(function ($row) {
                    $dateRaw = $row->getRawOriginal('date');
                    if (!$dateRaw) {
                        return true; // unschedule
                    }

                    $previouslyEnrolled = $this->participantId
                        ? $row->participants()->where('participant_id', $this->participantId)->exists()
                        : false;
                    if ($previouslyEnrolled) return true;

                    $now  = now();
                    $date = \Carbon\Carbon::parse($dateRaw)->startOfDay();

                    // Tanggal sudah lewat → sembunyikan
                    if ($date->isPast() && !$date->isToday()) {
                        return false;
                    }

                    // Tanggal hari ini tapi jam selesai sudah lewat → sembunyikan
                    $timeToRaw = $row->getRawOriginal('time_to');
                    if ($date->isToday() && $timeToRaw) {
                        $timeTo = \Carbon\Carbon::parse($dateRaw . ' ' . $timeToRaw);
                        return $timeTo->greaterThan($now);
                    }

                    return true;
                })
                ->map(function ($row) {
                    $dateRaw     = $row->getRawOriginal('date');
                    $timeFromRaw = $row->getRawOriginal('time_from');
                    $timeToRaw   = $row->getRawOriginal('time_to');
                    $now         = now();

                    $date  = $dateRaw ? \Carbon\Carbon::parse($dateRaw)->startOfDay() : null;
                    // Combine date+time so can_enter does not inherit "today" from a time-only cast
                    $start = ($dateRaw && $timeFromRaw)
                        ? \Carbon\Carbon::parse($dateRaw . ' ' . $timeFromRaw)
                        : null;

                    $canEnter = ! $date || (
                        ! $date->isFuture()
                        && (! $start || $start->lessThanOrEqualTo($now))
                    );
                    $previouslyEnrolled = $this->participantId
                        ? $row->participants()->where('participant_id', $this->participantId)->exists()
                        : false;
                    $scheduleId = app(ParticipantEnrollmentService::class)->forRequest(
                        (string) $this->participantId,
                        $row->course,
                        $row,
                    );
                    $hasActiveAccess = $scheduleId?->isAccessActive() ?? false;

                    $timeFromLabel = $timeFromRaw ? \Carbon\Carbon::parse($timeFromRaw)->format('H:i') : null;
                    $timeToLabel   = $timeToRaw ? \Carbon\Carbon::parse($timeToRaw)->format('H:i') : null;

                    return [
                        'id'          => $row->id,
                        'date'        => $dateRaw ? \Carbon\Carbon::parse($dateRaw)->format('Y-m-d') : $row->date,
                        'date_raw'    => $dateRaw,
                        'time'        => ($timeFromLabel && $timeToLabel) ? "{$timeFromLabel}-{$timeToLabel}" : $row->time,
                        'time_from'   => $timeFromLabel,
                        'quota'       => $row->quota,
                        'can_enter'   => (bool) ($canEnter || ($previouslyEnrolled && ! $hasActiveAccess)),
                        'start_label' => $timeFromLabel,
                        'is_active'   => $hasActiveAccess ? 1 : 0,
                    ];
                })
                ->values(),
            'reviews' => $reviews->map(function ($row) {
                return [
                    'name' => $row->user->name,
                    'star' => $row->star,
                    'description' => $row->description,
                    'created_at' => \Carbon\Carbon::parse($row->created_at)->diffForhumans()
                ];
            }),
            'trainers' => $course->trainers->map(fn ($trainer) => [
                'id' => $trainer->id,
                'name' => $trainer->name,
                'position' => $trainer->jabatan,
                'photo' => $trainer->photo_src,
            ])->values(),
            'rating' => $course->getRating()
        ]);
    }

    public function store(Request $request, Course $course, Schedule $schedule)
    {
        abort_unless((string) $schedule->course_id === (string) $course->id, 404);
        if (! app(\App\Services\Course\CourseAudience::class)->canEnroll($course, $request->user())) {
            return response()->json(['message' => 'OPD atau status kepegawaian Anda tidak memenuhi syarat kelas ini.'], 403);
        }

        try {
            DB::beginTransaction();

            $participantId = Auth::user()->id;

            $enrollmentService = app(ParticipantEnrollmentService::class);
            $enrollmentService->archiveExpired((string) $participantId, (string) $course->id);
            $enrollment = $enrollmentService->activate($course, $schedule, (string) $participantId);
            $expectedScheduleId = $course->access_mode === 'scheduled' ? $schedule->id : null;
            if ($enrollment->status !== 'active' || $enrollment->schedule_id !== $expectedScheduleId) {
                DB::rollBack();
                $message = $enrollment->status !== 'active' ? 'Akses kelas ini telah berakhir.' : 'Anda sudah terdaftar pada sesi kelas yang lain.';
                return response()->json(['status' => 409, 'message' => $message, 'data' => false], 409);
            }

            $this->course->generatePrePostTestVariant($participantId, $course);

            $schedule->participants()->syncWithoutDetaching([$participantId]);


            DB::commit();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Kelas berhasil diaktifkan.']);
            }

            return to_route('course.show', $course->slug);
        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);

            return response()->json(['message' => 'Aktivasi kelas belum berhasil. Silakan coba lagi dalam beberapa saat.'], 500);
        }
    }
}
