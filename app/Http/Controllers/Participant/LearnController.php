<?php

namespace App\Http\Controllers\Participant;

use App\Http\Controllers\Controller;
use App\Models\Course\Activity\Activity;
use App\Models\Course\Course;
use App\Models\Course\Schedule;
use App\Models\Learning\Learn;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\Activity\QuestionService;
use App\Services\Course\CourseService;
use App\Services\Learning\LearnService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
            if ((string) $initActivity !== (string) $activity->id) {
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
        $course = $this->course->findBySlug($course->slug, Auth::user());
        abort_unless($course, 404);

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
        if ($activity->type === 'meeting') {
            $request->validate(['status' => 'required|in:in_progress,done']);
        }

        if ($activity->type == 'task') {
            $request->validate([
                'file' => 'required|mimes:pdf|max:2048'
            ]);
        }

        try {
            DB::beginTransaction();
            $enrollment = \App\Models\Learning\Enrollment::query()->lockForUpdate()->find(request()->attributes->get('participant_enrollment')?->id);
            abort_unless($enrollment && app(\App\Services\Learning\ParticipantEnrollmentService::class)->canWrite($enrollment), 403, 'Masa akses kelas ini telah berakhir.');

            $stored = false;

            // Handle Pre-Test, Post-Test, Task
            if (!empty($request->all()) && ($activity->type == 'pre-test' || $activity->type == 'post-test' || $activity->type == 'task')) {
                $stored = $this->activity->storeActivity($this->participantId, $course->id, $activity->id, $request->all());
            }

            // Handle Meeting, Video, Literature (No answers required)
            if (empty($request->all()) && in_array($activity->type, ['meeting', 'video', 'literature'])) {
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

            $resetPostTest = $activity->type === 'post-test'
                && $this->learn->shouldResetAfterPostTest($this->participantId, $course->id, $activity->id);
            if ($resetPostTest) {
                $this->learn->resetFromPostTest($this->participantId, $course->id);
            }

            DB::commit();

            if ($resetPostTest) {
                return to_route('participant.learn.instruction.index', [
                    $course->slug, $schedule->id, $this->learn->getInititalActivityId($course->id),
                ])->with('danger', 'Nilai post-test belum mencapai batas kelulusan. Progres kelas direset; mulai kembali dari kegiatan pertama.');
            }

            if (!empty($request->all()) && $activity->type == 'meeting') {
                return to_route($request->input('status') === 'done' ? 'participant.learn.result.index' : 'participant.learn.index', [$course->slug, $schedule->id, $activity->id]);
            }

            if (!empty($request->all()) && ($activity->type == 'pre-test' || $activity->type == 'post-test')) {
                return to_route('participant.learn.result.index', [$course->slug, $schedule->id, $activity->id]);
            }

            return to_route('participant.learn.result.index', [$course->slug, $schedule->id, $activity->id]);

        } catch (\Throwable $th) {
            DB::rollBack();
            if ($th instanceof HttpException) throw $th;
            Log::error('Penyimpanan pembelajaran gagal.', ['event' => 'learn.store.failed', 'exception' => $th]);
            return to_route('participant.learn.index', [$course->slug, $schedule->id, $activity->id])
                ->withErrors(['error' => 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.']);
        }
    }

    public function instruction(Course $course, Schedule $schedule, Activity $activity)
    {
        if ($course->access_mode === 'scheduled') {
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
        $enrollment = \App\Models\Learning\Enrollment::query()->find(request()->attributes->get('participant_enrollment')?->id);
        abort_unless($enrollment && app(\App\Services\Learning\ParticipantEnrollmentService::class)->canWrite($enrollment), 403, 'Masa akses kelas ini telah berakhir.');
        // Prevent proceeding if minimum score not met
        if (in_array($activity->type, ['pre-test', 'post-test'])) {
            $isPassed = $this->learn->isPassedMinimumScore($this->participantId, $course->id, $activity->id);

            if (!$isPassed) {
                return redirect()->back()->withErrors(['error' => 'Nilai minimal dari aktivitas sebelumnya belum tercapai. Silahkan ulangi test ini.']);
            }
        }

        $learnItem = \App\Models\Learning\LearnItem::query()
            ->whereHas('learn', fn ($query) => $query->where('enrollment_id', $enrollment->id))
            ->where('activity_id', $activity->id)
            ->where('status', 'done')
            ->first();

        if (! $learnItem) {
            return to_route('participant.learn.index', [$course->slug, $schedule->id, $activity->id])
                ->withErrors(['error' => 'Selesaikan kegiatan ini sebelum melanjutkan.']);
        }

        $learnItem->update(['locked_at' => $learnItem->locked_at ?? now()]);
        $nextActivity = $this->activity->nextActivity($course->id, $activity->id);
        if (! $nextActivity) {
            return to_route('participant.rating.index', [$course->slug, $schedule->id]);
        }

        return to_route('participant.learn.instruction.index', [$course->slug, $schedule->id, $nextActivity]);
    }
}
