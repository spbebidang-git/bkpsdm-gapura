<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Course\Category;
use App\Models\Course\Course;
use App\Models\Course\Schedule;
use App\Services\Course\CourseAudience;
use App\Services\Course\CourseService;
use App\Services\Learning\ParticipantEnrollmentService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CourseController extends Controller
{
    protected $course;

    protected $participantId;

    public function __construct(CourseService $courseService)
    {
        $this->course = $courseService;
        $this->participantId = Auth::user()->id ?? null;
    }

    public function index(Request $request)
    {
        return $this->renderCatalog($request, 'kms');
    }

    public function lms(Request $request)
    {
        return $this->renderCatalog($request, 'lms');
    }

    public function kms(Request $request)
    {
        return $this->renderCatalog($request, 'kms');
    }

    protected function renderCatalog(Request $request, string $modality)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'visibility' => ['nullable', Rule::in(['public', 'private'])],
            'modality' => ['nullable', Rule::in(['kms', 'lms'])],
            'opd_id' => ['nullable', 'string', 'exists:opds,id'],
            'employee_status' => ['nullable', 'string', Rule::in(['CPNS', 'PNS', 'PPPK', 'PPPK Paruh Waktu'])],
            'category' => ['nullable', 'string', 'exists:categories,id'],
        ]);

        $filters['modality'] = $modality;

        $isKms = $modality === 'kms';

        return Inertia::render($isKms ? 'Course/Kms' : 'Course/Lms', [
            'courses' => $isKms && empty($filters['opd_id'])
                ? []
                : $this->course->getAllCourse($filters, $request->user(), restrictToAudience: false),
            'kms' => $isKms ? $this->course->kmsEntryOptions() : null,
            'categories' => $isKms ? null : Category::query()
                ->orderBy('title')
                ->get(['id', 'title']),
            'filters' => $filters,
        ]);
    }

    public function show($course)
    {
        $course = $this->course->findBrowseableBySlug($course);
        abort_unless($course, 404);
        if ($this->participantId) {
            app(ParticipantEnrollmentService::class)->archiveExpired((string) $this->participantId, (string) $course->id);
        }
        $course->loadMissing(['trainers.user.metas']);
        $reviews = $course->reviews ? $course->reviews->load('user') : [];
        $canEnroll = $this->participantId
            ? app(CourseAudience::class)->canEnroll($course, request()->user())
            : false;

        return Inertia::render('Course/Show', [
            'can_enroll' => $canEnroll,
            'course' => [
                'id' => $course->id,
                'modality' => $course->modality ?? 'lms',
                'type' => $course->type,
                'title' => $course->title,
                'employee_status' => $course->employeeStatus->pluck('title')->map(fn ($t) => trim($t))->join(', '),
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
                    if (! $dateRaw) {
                        return true; // unschedule
                    }

                    $previouslyEnrolled = $this->participantId
                        ? $row->participants()->where('participant_id', $this->participantId)->exists()
                        : false;
                    if ($previouslyEnrolled) {
                        return true;
                    }

                    $now = now();
                    $date = Carbon::parse($dateRaw)->startOfDay();

                    // Tanggal sudah lewat → sembunyikan
                    if ($date->isPast() && ! $date->isToday()) {
                        return false;
                    }

                    // Tanggal hari ini tapi jam selesai sudah lewat → sembunyikan
                    $timeToRaw = $row->getRawOriginal('time_to');
                    if ($date->isToday() && $timeToRaw) {
                        $timeTo = Carbon::parse($dateRaw.' '.$timeToRaw);

                        return $timeTo->greaterThan($now);
                    }

                    return true;
                })
                ->map(function ($row) {
                    $dateRaw = $row->getRawOriginal('date');
                    $timeFromRaw = $row->getRawOriginal('time_from');
                    $timeToRaw = $row->getRawOriginal('time_to');
                    $now = now();

                    $date = $dateRaw ? Carbon::parse($dateRaw)->startOfDay() : null;
                    // Combine date+time so can_enter does not inherit "today" from a time-only cast
                    $start = ($dateRaw && $timeFromRaw)
                        ? Carbon::parse($dateRaw.' '.$timeFromRaw)
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

                    $timeFromLabel = $timeFromRaw ? Carbon::parse($timeFromRaw)->format('H:i') : null;
                    $timeToLabel = $timeToRaw ? Carbon::parse($timeToRaw)->format('H:i') : null;

                    return [
                        'id' => $row->id,
                        'date' => $dateRaw ? Carbon::parse($dateRaw)->format('Y-m-d') : $row->date,
                        'date_raw' => $dateRaw,
                        'time' => ($timeFromLabel && $timeToLabel) ? "{$timeFromLabel}-{$timeToLabel}" : $row->time,
                        'time_from' => $timeFromLabel,
                        'quota' => $row->quota,
                        'can_enter' => (bool) ($canEnter || ($previouslyEnrolled && ! $hasActiveAccess)),
                        'start_label' => $timeFromLabel,
                        'is_active' => $hasActiveAccess ? 1 : 0,
                    ];
                })
                ->values(),
            'reviews' => $reviews->map(function ($row) {
                return [
                    'name' => $row->user->name,
                    'star' => $row->star,
                    'description' => $row->description,
                    'created_at' => Carbon::parse($row->created_at)->diffForhumans(),
                ];
            }),
            'trainers' => $course->trainers->map(fn ($trainer) => [
                'id' => $trainer->id,
                'name' => $trainer->name,
                'position' => $trainer->jabatan,
                'photo' => $trainer->photo_src,
            ])->values(),
            'rating' => $course->getRating(),
        ]);
    }

    public function store(Request $request, Course $course, Schedule $schedule)
    {
        abort_unless((string) $schedule->course_id === (string) $course->id, 404);
        if (! app(CourseAudience::class)->canEnroll($course, $request->user())) {
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
