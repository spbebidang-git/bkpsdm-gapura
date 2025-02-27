<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Course\Activity;
use App\Models\Course\Course;
use App\Models\Learn\Learn;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\Activity\QuestionService;
use App\Services\Course\CourseService;
use App\Services\Learn\LearnService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class LearnController extends Controller
{
    protected $activity, $course, $participantId, $learn, $question;

    public function __construct(
        ActivityService $activityService,
        CourseService $courseService,
        LearnService $learnService,
        QuestionService $questionService
    ) {
        $this->activity = $activityService;
        $this->course = $courseService;
        $this->participantId = Auth::user()->id;
        $this->learn = $learnService;
        $this->question = $questionService;
    }

    public function index(Course $course, $schedule, Activity $activity)
    {
        if ($activity->type === 'post-test' && request()->loop) {
            // dd(123);
            // Check Total looping
            $checkTrial = $this->learn->checkTrial($this->participantId, $course->id, $activity->id);
            // dd($checkTrial);
            if ($checkTrial) {
                if ($checkTrial === 3) {
                    $this->learn->resetFromPostTest($this->participantId, $course->id);

                    return to_route('participant.learn.instruction.index', [$course->slug, 1, $this->learn->getInititalActivityId($course->id)]);
                }

                $this->learn->setTrialTotal($checkTrial->learnItems->first());
                // return response(null, 200);
            }
        }

        $course = $this->course->findBySlug($course->slug);
        $this->learn->resetLearning($this->participantId, $course->id, 10);

        if (!$this->learn->isLearned($this->participantId, $course->id)) {
            $initActivity = $this->learn->getInititalActivityId($course->id);
            if ($initActivity !== intval($activity->id)) {
                return to_route('participant.learn.instruction.index', [$course->slug, 1, $initActivity]);
            }
        } else {
            $validOpenActivity = $this->learn->checkValidOpenActivity($this->participantId, $course->id, $activity->id);
            // dd($validOpenActivity);
            if (is_array($validOpenActivity)) {
                return redirect()->back()->withErrors(['error' => 'Kamu belum menyelesaikan kegiatan sebelumnya. Selesaikan sesuai urutan kegiatan.']);
            }
        }
        $timer = 0;

        return Inertia::render('Learn/Material/Index', [
            'course' => $course,
            'progress' => Learn::where('participant_id', $this->participantId)->first()->progress ?? '0 %',
            'activities' => $this->activity->getActivityByParticipant($this->participantId, $course->id),
            'activity' => $this->activity->getDetailActivityParticipant($this->participantId, $activity->id),
            'activity_id' => $activity->id,
        ]);
    }

    public function store(Course $course, $schedule, Activity $activity, Request $request)
    {
        // dd(count($request->all()));
        # validate pre test & post test
        // if ($activity->type == 'pre-test' || $activity->type == 'post-test') {
        //     if (!$this->question->validateQuestion(count($request->all()), $this->participantId, $activity->id)) {
        //         return redirect()->back()->withErrors(['error' => 'Please answer all questions']);
        //     }
        // }


        try {
            DB::beginTransaction();



            # For Pre Test, Post Test & Task
            if (!empty($request->all()) && ($activity->type == 'pre-test' || $activity->type == 'post-test')) {
                $this->activity->storeActivity($this->participantId, $course->id, $activity->id, $request->all());
            }
            # For Task
            if ($activity->type  == 'task') {
                $this->activity->storeActivity($this->participantId, $course->id, $activity->id, $request->all());
            }

            # For Meeting, Video & Literature
            if (empty($request->all()) && ($activity->type == 'meeting' || $activity->type == 'video' || $activity->type == 'literature')) {
                $this->activity->storeActivity($this->participantId, $course->id, $activity->id, $request->all());
            }

            # For Submit Meeting 
            if (!empty($request->all()) && $activity->type == 'meeting') {

                $q = $this->activity->storeActivity($this->participantId, $course->id, $activity->id, $request->all());
                // dd($q);
                if (!$q) {
                    return to_route('participant.learn.index', [$course->slug, $course->id, $activity->id])->with('danger', 'error');
                }
            }

            DB::commit();

            if (!empty($request->all()) && $activity->type == 'meeting') {
                return response(null, 200);
            }

            if (!empty($request->all()) && ($activity->type == 'pre-test' || $activity->type == 'post-test')) {
                return to_route('participant.learn.result.index', [$course->slug, $schedule, $activity->id]);
            }

            return to_route('participant.learn.result.index', [$course->slug, $schedule, $activity->id]);

            // // if question test


        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);
            return to_route('participant.learn.index', [$course->slug, 1, $activity->id]);
        }
    }

    public function instruction(Course $course, $scheduleId, Activity $activity)
    {
        if (!$this->learn->isLearned($this->participantId, $course->id)) {
            $initActivity = $this->learn->getInititalActivityId($course->id);
            if ($initActivity !== intval($activity->id)) {
                return to_route('participant.learn.instruction.index', [$course->slug, 1, $initActivity]);
            }
        } else {
            $validOpenActivity = $this->learn->checkValidOpenActivity($this->participantId, $course->id, $activity->id);
            // dd($validOpenActivity);
            if (is_array($validOpenActivity)) {
                return redirect()->back()->withErrors(['error' => 'Kamu belum menyelesaikan kegiatan sebelumnya. Selesaikan sesuai urutan kegiatan.']);
            }
        }

        $instruction = $activity->instruction;

        return Inertia::render('Learn/Instruction/Index', [
            'course' => $course,
            'progress' => Learn::where('participant_id', $this->participantId)->first()->progress ?? '0 %',
            'activities' => $this->activity->getActivityByParticipant($this->participantId, $course->id),
            'activity' => $this->activity->getDetailActivityParticipant($this->participantId, $activity->id),
            'activity_id' => $activity->id,
            'question_result' =>  null,
            'instruction' => $instruction
        ]);
    }

    public function storeInstruction(Course $course, $schedule, Activity $activity, Request $request)
    {
        return to_route('participant.learn.index', [$course->slug, $schedule, $activity->id]);
    }

    public function result(Course $course, $schedule, Activity $activity)
    {
        if ($activity->type == 'pre-test' || $activity->type == 'post-test') {
            $questionResult = $this->learn->getResultQuestion($this->participantId, $course->id, $activity->id);

            return Inertia::render('Learn/Result/Index', [
                'course' => $course,
                'progress' => Learn::where('participant_id', $this->participantId)->first()->progress ?? '0 %',
                'activities' => $this->activity->getActivityByParticipant($this->participantId, $course->id),
                'activity' => $this->activity->getDetailActivityParticipant($this->participantId, $activity->id),
                'activity_id' => $activity->id,
                'question_result' => $questionResult ?? null
            ]);
        }

        return Inertia::render('Learn/Result/Index', [
            'course' => $course,
            'progress' => Learn::where('participant_id', $this->participantId)->first()->progress ?? '0 %',
            'activities' => $this->activity->getActivityByParticipant($this->participantId, $course->id),
            'activity' => $this->activity->getDetailActivityParticipant($this->participantId, $activity->id),
            'activity_id' => $activity->id,
            'question_result' =>  null,
        ]);
    }

    public function storeResult(Course $course, $schedule, Activity $activity, Request $request)
    {
        $nextActivity =  $this->activity->nextActivity($course->id, $activity->id);
        // dd($activity);
        if (!$nextActivity) {
            return to_route('participant.rating.index', [$course->slug, 1])->with('success', 'Saved');
        }

        if (($activity->type == 'pre-test' || $activity->type == 'post-test') && empty($request->all())) {
            return to_route('participant.learn.instruction.index', [$course->slug, 1, $nextActivity])->with('success', 'Saved');
        }

        return to_route('participant.learn.instruction.index', [$course->slug, 1, $nextActivity])->with('success', 'Saved');
    }
}
