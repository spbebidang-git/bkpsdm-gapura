<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Course\Course;
use App\Models\Course\Schedule;
use App\Models\Learn\Learn;
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
                'employee_status' => $course->employee_status,
                'short_description' => $course->short_description,
                'description' => $course->description,
                'teaser_video' => $course->teaser_video,
                'slug' => $course->slug,
                'quota' => $course->quota,
                'thumbnail' => $course->thumbnail_src,
                'category' => $course->category->title,
                'instructor' => $course->instructor->name,
                'total_participant' => $course->total_participant,
                'status' => $course->status,
            ],
            'schedules' => $course->schedules
                ->filter(function ($row) {
                    $now  = \Carbon\Carbon::now();
                    $date = \Carbon\Carbon::parse($row->getRawOriginal('date'));

                    // Tanggal sudah lewat → sembunyikan
                    if ($date->isPast() && !$date->isToday()) {
                        return false;
                    }

                    // Tanggal hari ini tapi jam selesai sudah lewat → sembunyikan
                    if ($date->isToday() && $row->time_to) {
                        // Gunakan parse() agar mendukung format H:i maupun H:i:s
                        $timeTo = \Carbon\Carbon::parse($row->time_to);
                        return $timeTo->greaterThan($now);
                    }

                    return true;
                })
                ->map(function ($row) {
                    $now     = \Carbon\Carbon::now();
                    $date    = \Carbon\Carbon::parse($row->getRawOriginal('date'))->startOfDay();
                    $timeFrom = $row->time_from ? \Carbon\Carbon::parse($row->time_from) : null;

                    // Kelas bisa dimasuki jika: tanggal hari ini DAN jam sudah mulai (atau unschedule)
                    $canEnter = !$date->isFuture() // bukan hari depan
                        && (!$timeFrom || $timeFrom->lessThanOrEqualTo($now));

                    return [
                        'id'          => $row->id,
                        'date'        => $row->date,
                        'date_raw'    => $row->getRawOriginal('date'),
                        'time'        => $row->time,
                        'time_from'   => $row->time_from,
                        'quota'       => $row->quota,
                        'can_enter'   => $canEnter,
                        'start_label' => $timeFrom ? $timeFrom->format('H:i') : null,
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
