<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Course\Activity;
use App\Models\Course\Course;
use App\Models\Learn\Learn;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\CourseService;
use App\Services\Learn\LearnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LearnController extends Controller
{
    protected $activity, $course, $participantId, $learn;

    public function __construct(
        ActivityService $activityService,
        CourseService $courseService,
        LearnService $learnService
    ) {
        $this->activity = $activityService;
        $this->course = $courseService;
        $this->participantId = Auth::user()->id;
        $this->learn = $learnService;
    }

    public function index($slug, $scheduleId, $activityId = null)
    {
        $course = $this->course->findBySlug($slug);
        $activity = Activity::find($activityId);

        if ($activity->type == 'pre-test' || $activity->type == 'post-test') {
            $questionResult = $this->learn->getResultQuestion($this->participantId, $course->id, $activityId);
            return Inertia::render('Learn/Show', [
                'course' => $course,
                'progress' => Learn::where('participant_id', $this->participantId)->first()->progress ?? '0 %',
                'activities' => $this->activity->getActivityByCourse($course->id),
                'activity' => $this->activity->getDetailActivityParticipant($activityId),
                'activity_id' => $activityId,
                'question_result' => $questionResult ?? null
            ]);
        }

        return Inertia::render('Learn/Show', [
            'course' => $course,
            'progress' => Learn::where('participant_id', $this->participantId)->first()->progress ?? '0 %',
            'activities' => $this->activity->getActivityByCourse($course->id),
            'activity' => $this->activity->getDetailActivityParticipant($activityId),
            'activity_id' => $activityId,
        ]);
    }

    public function store(Request $request, Course $course, $scheduleId, $activityId)
    {
        $activity = Activity::find($activityId);

        try {
            DB::beginTransaction();

            # For Pre Test, Post Test & Task
            if (!empty($request->all()) && ($activity->type == 'pre-test' || $activity->type == 'post-test' || $activity->type  == 'task')) {
                $this->activity->storeActivity($this->participantId, $course->id, $activityId, $request->all());
            }

            # For Meeting, Video & Literature
            if (empty($request->all()) && ($activity->type == 'meeting' || $activity->type == 'video' || $activity->type == 'literature')) {
                $this->activity->storeActivity($this->participantId, $course->id, $activityId, $request->all());
            }

            # For Submit Meeting 
            if (!empty($request->all()) && $activity->type == 'meeting') {
                $this->activity->storeActivity($this->participantId, $course->id, $activityId, $request->all());
            }

            DB::commit();

            if (!empty($request->all()) && $activity->type == 'meeting') {
                return response(null, 200);
            }

            if (!empty($request->all()) && ($activity->type == 'pre-test' || $activity->type == 'post-test')) {
                return response(null, 200);
            }

            // // if question test
            // $activity = Activity::find($activityId);

            $nextActivity =  $this->activity->nextActivity($course->id, $activityId);

            if (!$nextActivity) {
                return to_route('participant.rating.index', [$course->slug, 1]);
            }

            if (($activity->type == 'pre-test' || $activity->type == 'post-test') && empty($request->all())) {
                return to_route('participant.learn.index', [$course->slug, 1, $nextActivity]);
            }

            return to_route('participant.learn.index', [$course->slug, 1, $nextActivity]);
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);
            return to_route('participant.learn.index', [$course->slug, 1, $activityId]);
        }
    }
}
