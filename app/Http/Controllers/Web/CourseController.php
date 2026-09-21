<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Course\Course;
use App\Models\Course\Schedule;
use App\Models\Learning\Learn;
use App\Services\Course\CourseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CourseController extends Controller
{
    protected $course, $participantId;

    public function __construct(CourseService $courseService)
    {
        $this->course = $courseService;
        $this->participantId = Auth::user()->id ?? null;
    }

    public function index()
    {
        return Inertia::render('Course/Index', [
            'courses' => $this->course->getAllCourse()
        ]);
    }

    public function show($course)
    {
        $course = $this->course->findBySlug($course);
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
                'status' => $course->status,
            ],
            'schedules' => $course->schedules
                ->filter(function ($row) {
                    $dateRaw = $row->getRawOriginal('date');
                    if (!$dateRaw) {
                        return true; // unschedule
                    }

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

                    $canEnter = $date
                        && !$date->isFuture()
                        && (!$start || $start->lessThanOrEqualTo($now));

                    $timeFromLabel = $timeFromRaw ? \Carbon\Carbon::parse($timeFromRaw)->format('H:i') : null;
                    $timeToLabel   = $timeToRaw ? \Carbon\Carbon::parse($timeToRaw)->format('H:i') : null;

                    return [
                        'id'          => $row->id,
                        'date'        => $dateRaw ? \Carbon\Carbon::parse($dateRaw)->format('Y-m-d') : $row->date,
                        'date_raw'    => $dateRaw,
                        'time'        => ($timeFromLabel && $timeToLabel) ? "{$timeFromLabel}-{$timeToLabel}" : $row->time,
                        'time_from'   => $timeFromLabel,
                        'quota'       => $row->quota,
                        'can_enter'   => (bool) $canEnter,
                        'start_label' => $timeFromLabel,
                        'is_active'   => $row->participants()->where('participant_id', $this->participantId)->count() == 1 ? 1 : 0,
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
            'rating' => $course->getRating()
        ]);
    }

    public function store(Request $request, Course $course, Schedule $schedule)
    {
        // dd($this->course->checkEmployeeStatusCourse(Auth::user()->id, $course));
        # check valid employee status
        if (!$this->course->checkEmployeeStatusCourse(Auth::user()->id, $course)) {
            return response()->json([
                'status' => 400,
                'message' => 'Kamu tidak memenuhi syarat untuk mengikuti pelatihan ini.',
                'data' => false
            ], 400);
        }

        try {
            DB::beginTransaction();

            $participantId = Auth::user()->id;

            $variant = $this->course->generatePrePostTestVariant($participantId, $course);

            $schedule->participants()->attach($participantId);


            DB::commit();

            return to_route('course.show', $course->slug);
            // return response(null);
        } catch (\Throwable $th) {
            DB::rollBack();

            return response('Something went wrong.', 200);
        }
    }
}
