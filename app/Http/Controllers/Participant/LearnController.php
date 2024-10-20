<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Course\Course;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\CourseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LearnController extends Controller
{
    protected $activity, $course, $participantId;

    public function __construct(
        ActivityService $activityService,
        CourseService $courseService
    ) {
        $this->activity = $activityService;
        $this->course = $courseService;
        $this->participantId = Auth::user()->id;
    }

    public function index($slug, $scheduleId, $activityId = null)
    {
        $course = $this->course->findBySlug($slug);

        return Inertia::render('Learn/Show', [
            'course' => $course,
            'activities' => $this->activity->getActivityByCourse($course->id),
            'activity' => $this->activity->getDetailActivity($activityId),
            'activity_id' => $activityId
        ]);
    }

    public function store(Request $request, Course $course, $scheduleId, $activityId)
    {
        $nextActivity =  $this->activity->nextActivity($course->id, $activityId);
        if (!$nextActivity) {
            return to_route('participant.course.index');
        }

        try {
            DB::beginTransaction();

            $this->activity->storeActivity($this->participantId, $course->id, $activityId, $request->all());

            DB::commit();

            return to_route('participant.learn.index', [$course->slug, 1, $nextActivity]);
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('participant.learn.index', [$course->slug, 1, $activityId]);
        }
    }
}
