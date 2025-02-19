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
        $reviews = $course->reviews->load('user');

        return Inertia::render('Course/Show', [
            'course' => [
                'id' => $course->id,
                'type' => $course->type,
                'title' => $course->title,
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
            'schedules' => $course->schedules->map(function ($row) {
                return [
                    'id' => $row->id,
                    'date' => $row->date,
                    'time' => $row->time,
                    'quota' => $row->quota,
                    'is_active' => $row->participants()->where('participant_id', $this->participantId)->count() == 1 ? 1 : 0,
                ];
            }),
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

        try {
            DB::beginTransaction();

            $participantId = Auth::user()->id;

            $variant = $this->course->generatePrePostTestVariant($participantId, $course);
            // dd($variant);

            $schedule->participants()->attach($participantId);

            // Learn::create([
            //     'participant_id' => $participantId,
            //     'course_id' => $course->id,
            // ]);

            DB::commit();

            return response(null, 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);
            return response(null, 200);
        }
    }
}
