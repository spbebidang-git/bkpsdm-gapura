<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Course\Activity;
use App\Models\Course\Course;
use App\Models\Course\Schedule;
use App\Models\Learn\Learn;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\Activity\QuestionService;
use App\Services\Course\CourseService;
use App\Services\Learn\LearnService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
        $this->participantId = Auth::id(); // Optimized Auth call
        $this->learn = $learnService;
        $this->question = $questionService;
    }

    /**
     * Helper to check learning sequence and prerequisites.
     * Returns a redirect response if check fails, or null if passes.
     */
    private function checkLearningSequence(Course $course, Schedule $schedule, Activity $activity)
    {
        if (!$this->learn->isLearned($this->participantId, $course->id)) {
            $initActivity = $this->learn->getInititalActivityId($course->id);
            if ($initActivity !== intval($activity->id)) {
                return to_route('participant.learn.instruction.index', [$course->slug, $schedule->id, $initActivity]);
            }
        } else {
            // Check previous activity score (if pre-test/post-test)
            $prev = $this->activity->getPreviousActivity($course->id, $activity->id);

            if ($prev && in_array($prev->type, ['pre-test', 'post-test'])) {
                $passed = $this->learn->isPassedMinimumScore($this->participantId, $course->id, $prev->id);

                if (!$passed) {
                    return redirect()->back()->withErrors(['error' => 'Nilai minimal dari aktivitas sebelumnya belum tercapai.']);
                }
            }

            $validOpenActivity = $this->learn->checkValidOpenActivity($this->participantId, $course->id, $activity->id);

            if (is_array($validOpenActivity)) {
                return redirect()->back()->withErrors(['error' => 'Kamu belum menyelesaikan kegiatan sebelumnya. Selesaikan sesuai urutan kegiatan.']);
            }
        }

        return null;
    }

    public function index(Course $course, Schedule $schedule, Activity $activity)
    {
        // Handle Post-Test Trial Loop
        if ($activity->type === 'post-test' && request()->loop) {
            $checkTrial = $this->learn->checkTrial($this->participantId, $course->id, $activity->id);
            if ($checkTrial) {
                if ($checkTrial === 2) {
                    return to_route('participant.learn.instruction.index', [$course->slug, $schedule->id, $this->learn->getInititalActivityId($course->id)]);
                }
                $this->learn->setTrialTotal($checkTrial->learnItems->first());
            }
        }

        $course = $this->course->findBySlug($course->slug);

        // Check Sequence
        $sequenceCheck = $this->checkLearningSequence($course, $schedule, $activity);
        if ($sequenceCheck) {
            return $sequenceCheck;
        }

        return Inertia::render('Learn/Material/Index', [
            'course' => $course,
            'progress' => Learn::where('participant_id', $this->participantId)->where('course_id', $course->id)->first()->progress ?? '0 %',
            'activities' => $this->activity->getActivityByParticipant($this->participantId, $course->id),
            'activity' => $this->activity->getDetailActivityParticipant($this->participantId, $activity->id),
            'activity_id' => $activity->id,
            'schedule' => $schedule
        ]);
    }

    public function store(Course $course, Schedule $schedule, Activity $activity, Request $request)
    {
        if ($activity->type == 'task') {
            $request->validate([
                'file' => 'required|mimes:pdf|max:2048'
            ]);
        }

        try {
            DB::beginTransaction();

            $stored = false;

            // Handle Pre-Test, Post-Test, Task, Kuesioner
            if (!empty($request->all()) && in_array($activity->type, ['pre-test', 'post-test', 'task', 'questionnaire'])) {
                $stored = $this->activity->storeActivity($this->participantId, $course->id, $activity->id, $request->all());
            }

            // Handle Meeting, Video, Literature, H5P, SCORM (No answers required)
            if (empty($request->all()) && in_array($activity->type, ['meeting', 'video', 'literature', 'h5p', 'scorm'])) {
                $stored = $this->activity->storeActivity($this->participantId, $course->id, $activity->id, $request->all());
            }

            // Specific handler for Meeting submission if needed (logic preserved from original)
            if (!empty($request->all()) && $activity->type == 'meeting') {
                $stored = $this->activity->storeActivity($this->participantId, $course->id, $activity->id, $request->all());
                if (!$stored) {
                    DB::rollBack();
                    return to_route('participant.learn.index', [$course->slug, $schedule->id, $activity->id])->with('danger', 'error');
                }
            }

            DB::commit();

            if (!empty($request->all()) && $activity->type == 'meeting') {
                return response(null, 200);
            }

            if (!empty($request->all()) && ($activity->type == 'pre-test' || $activity->type == 'post-test')) {
                return to_route('participant.learn.result.index', [$course->slug, $schedule->id, $activity->id]);
            }

            return to_route('participant.learn.result.index', [$course->slug, $schedule->id, $activity->id]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Kesalahan per pertanyaan (mis. kuesioner) harus sampai ke form,
            // bukan ditimpa pesan galat umum.
            DB::rollBack();
            throw $e;
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('LearnController@store Error: ' . $th->getMessage());
            return to_route('participant.learn.index', [$course->slug, $schedule->id, $activity->id])
                ->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.']);
        }
    }

    public function instruction(Course $course, Schedule $schedule, Activity $activity)
    {
        if ($course->type === 'schedule') {
            $now          = Carbon::now();
            $scheduleDate = Carbon::parse($schedule->getRawOriginal('date') ?? $schedule->date)->startOfDay();

            $isFutureDate = $scheduleDate->greaterThan($now->copy()->startOfDay());
            $isTodayButNotYetStarted = $scheduleDate->isToday()
                && Carbon::parse($schedule->time_from)->greaterThan($now);

            if ($isFutureDate || $isTodayButNotYetStarted) {
                return to_route('participant.course.index')->with('danger', 'Kelas belum dimulai. Silakan masuk sesuai jadwal.');
            }
        }

        // Check Sequence
        $sequenceCheck = $this->checkLearningSequence($course, $schedule, $activity);
        if ($sequenceCheck) {
            return $sequenceCheck;
        }

        $instruction = $activity->instruction;

        return Inertia::render('Learn/Instruction/Index', [
            'course' => $course,
            'progress' => Learn::where('participant_id', $this->participantId)->where('course_id', $course->id)->first()->progress ?? '0 %',
            'activities' => $this->activity->getActivityByParticipant($this->participantId, $course->id),
            'activity' => $this->activity->getDetailActivityParticipant($this->participantId, $activity->id),
            'activity_id' => $activity->id,
            'question_result' => null,
            'instruction' => $instruction,
            'schedule' => $schedule
        ]);
    }

    public function storeInstruction(Course $course, Schedule $schedule, Activity $activity, Request $request)
    {
        return to_route('participant.learn.index', [$course->slug, $schedule->id, $activity->id]);
    }

    public function result(Course $course, Schedule $schedule, Activity $activity)
    {
        $questionResult = null;
        if ($activity->type == 'pre-test' || $activity->type == 'post-test') {
            $questionResult = $this->learn->getResultQuestion($this->participantId, $course->id, $activity->id);
        }

        return Inertia::render('Learn/Result/Index', [
            'course' => $course,
            'progress' => Learn::where('participant_id', $this->participantId)->where('course_id', $course->id)->first()->progress ?? '0 %',
            'activities' => $this->activity->getActivityByParticipant($this->participantId, $course->id),
            'activity' => $this->activity->getDetailActivityParticipant($this->participantId, $activity->id),
            'activity_id' => $activity->id,
            'question_result' => $questionResult,
            'schedule' => $schedule
        ]);
    }

    public function storeResult(Course $course, Schedule $schedule, Activity $activity, Request $request)
    {
        $nextActivity = $this->activity->nextActivity($course->id, $activity->id);

        if (!$nextActivity) {
            return to_route('participant.rating.index', [$course->slug, $schedule->id])->with('success', 'Saved');
        }

        // Prevent proceeding if minimum score not met
        if (in_array($activity->type, ['pre-test', 'post-test'])) {
            $isPassed = $this->learn->isPassedMinimumScore($this->participantId, $course->id, $activity->id);

            if (!$isPassed) {
                return redirect()->back()->withErrors(['error' => 'Nilai minimal dari aktivitas sebelumnya belum tercapai. Silahkan ulangi test ini.']);
            }
        }

        return to_route('participant.learn.instruction.index', [$course->slug, $schedule->id, $nextActivity])->with('success', 'Saved');
    }
}

