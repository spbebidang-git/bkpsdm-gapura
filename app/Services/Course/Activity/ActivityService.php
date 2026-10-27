<?php

namespace App\Services\Course\Activity;

use App\Models\Course\Activity;
use App\Models\Course\Interactive;
use App\Models\Course\Question;
use App\Models\Course\Question\QuestionVariant;
use App\Models\Learn\Learn;

class ActivityService
{
    protected $question, $task, $interactive, $literature, $h5p, $scorm, $questionnaire;

    public function  __construct(
        QuestionService $questionService,
        TaskService $taskService,
        InteractiveService $interactiveService,
        LiteratureService $literatureService,
        H5pService $h5pService,
        ScormService $scormService,
        QuestionnaireService $questionnaireService
    ) {
        $this->question = $questionService;
        $this->task = $taskService;
        $this->interactive = $interactiveService;
        $this->literature = $literatureService;
        $this->h5p = $h5pService;
        $this->scorm = $scormService;
        $this->questionnaire = $questionnaireService;
    }

    public function getActivityByCourse($courseId)
    {
        return Activity::with('h5p')
            ->where('course_id', $courseId)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(function ($row) {
                $endMeeting = null;
                if ($row->type == 'meeting') {
                    $endMeeting = Interactive::where('activity_id', $row->id)->first()->end_meeting;
                }

                // $hasImported = null;
                // if ($row->type == 'pre-test' || $row->type == 'post-test') {
                //     $hasImported = Question::where('activity_id', $row->id)->first()->has_imported;
                // }

                $hasImported = false;

                if (in_array($row->type, ['pre-test', 'post-test'])) {

                    $question = Question::where('activity_id', $row->id)->first();

                    if ($question) {
                        $hasImported =
                            $question->has_imported == 1
                            || QuestionVariant::where('question_id', $question->id)->exists();
                    }
                }

                return [
                    'id' => $row->id,
                    'course_id' => $row->course_id,
                    'type' => $row->type,
                    'title' => $row->title,
                    'timer' => $row->timer,
                    'value' => $row->value,
                    'end_meeting' => $endMeeting,
                    'has_imported' => $hasImported,
                    'has_package' => $row->type === 'h5p'
                        ? $this->h5p->isReady($row->h5p)
                        : null,
                    'status' => 0
                ];
            });
    }

    public function getActivityByParticipant($participantId, $courseId)
    {
        return Activity::with(['learnItem'])
            ->where('course_id', $courseId)
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(function ($row) use ($participantId) {
                return [
                    'id' => $row->id,
                    'course_id' => $row->course_id,
                    'type' => $row->type,
                    'title' => $row->title,
                    'value' => $row->value,
                    'timer' => $row->timer,
                    'value' => $row->value,
                    'status' => $this->getStatus($participantId, $row->course_id, $row->id)
                ];
            });
    }

    public function getStatus($participantId, $courseId, $activityId)
    {
        $learn = Learn::with(['learnItems' => function ($query) use ($activityId) {
            return $query->where('activity_id', $activityId);
        }])
            ->where('participant_id', $participantId)
            ->where('course_id', $courseId)
            ->first();

        // dd($learn->learnItems);
        if (!$learn || !$learn->learnItems->first()) {
            return 0;
        }

        return $learn->learnItems->first()->status;
    }

    public function createActivity($data)
    {
        if (isset($data['instruction'])) {
            $data['instruction'] = sanitize_html($data['instruction']);
        }
        // Aktivitas baru diletakkan paling akhir (position = maks + 10).
        if (!isset($data['position']) && isset($data['course_id'])) {
            $maxPosition = Activity::where('course_id', $data['course_id'])->max('position') ?? 0;
            $data['position'] = $maxPosition + 10;
        }
        return Activity::create($data);
    }

    public function findById($activityId)
    {
        return Activity::find($activityId);
    }

    public function getDetailActivity($activityId)
    {
        $activity = $this->findById($activityId);

        if ($activity->type === 'pre-test') {
            return $this->question->getPreTest($activity);
        }

        if ($activity->type === 'post-test') {
            return $this->question->getPostTest($activity);
        }

        if ($activity->type === 'task') {
            // getTask() mengabaikan argumen pertama; konteks instruktur tidak
            // punya peserta, jadi dilewatkan null.
            return $this->task->getTask(null, $activity);
        }

        if ($activity->type === 'meeting') {
            return $this->interactive->getMeeting($activity);
        }

        if ($activity->type === 'video') {
            return $this->interactive->getVideo($activity);
        }

        if ($activity->type === 'literature') {
            return $this->literature->getLiterature($activity);
        }

        if ($activity->type === 'h5p') {
            return $this->h5p->getH5p($activity);
        }

        if ($activity->type === 'scorm') {
            return $this->scorm->getScorm($activity);
        }

        if ($activity->type === 'questionnaire') {
            return $this->questionnaire->getQuestionnaire($activity);
        }
    }

    public function getDetailActivityParticipant($participantId, $activityId)
    {
        $activity = $this->findById($activityId);

        if ($activity->type === 'pre-test') {
            return $this->question->getPreTestParticipant($participantId, $activity);
        }

        if ($activity->type === 'post-test') {
            return $this->question->getPostTestParticipant($participantId, $activity);
        }

        if ($activity->type === 'task') {
            return $this->task->getTask($participantId, $activity);
        }

        if ($activity->type === 'meeting') {
            return $this->interactive->getMeetingParticipant($participantId, $activity);
        }

        if ($activity->type === 'video') {
            return $this->interactive->getVideo($activity, $participantId);
        }

        if ($activity->type === 'literature') {
            return $this->literature->getLiterature($activity);
        }

        if ($activity->type === 'h5p') {
            return $this->h5p->getH5p($activity);
        }

        if ($activity->type === 'scorm') {
            return $this->scorm->getScorm($activity);
        }

        if ($activity->type === 'questionnaire') {
            return $this->questionnaire->getQuestionnaire($activity, $participantId);
        }
    }

    public function storeActivity($participantId, $courseId, $activityId, $request)
    {
        $activity = $this->findById($activityId);

        if ($activity->type == 'pre-test') {
            return $this->question->storePreTest($participantId, $courseId, $activityId, $request);
        }

        if ($activity->type == 'post-test') {
            return $this->question->storePostTest($participantId, $courseId, $activityId, $request);
        }

        if ($activity->type == 'task') {
            return $this->task->storeTaskParticipant($participantId, $courseId, $activityId, $request);
        }

        if ($activity->type == 'meeting') {
            return $this->interactive->storeMeeting($participantId, $courseId, $activityId, $request);
        }

        if ($activity->type == 'video') {
            return $this->interactive->storeVideo($participantId, $courseId, $activityId, $request);
        }

        if ($activity->type == 'literature') {
            return $this->literature->storeLiterature($participantId, $courseId, $activityId, $request);
        }

        if ($activity->type == 'h5p') {
            return $this->h5p->storeH5p($participantId, $courseId, $activityId, $request);
        }

        if ($activity->type == 'scorm') {
            return $this->scorm->storeScorm($participantId, $courseId, $activityId, $request);
        }

        if ($activity->type == 'questionnaire') {
            return $this->questionnaire->storeQuestionnaire($participantId, $courseId, $activityId, $request);
        }
    }

    public function updateActivity($request)
    {
        $activity = Activity::find($request['activity_id']);

        $activity->update([
            'title' => $request['title'],
            'value' => $request['value'],
            'instruction' => isset($request['instruction']) ? sanitize_html($request['instruction']) : null,
        ]);

        if ($activity->type === 'pre-test' || $activity->type === 'post-test') {
            $activity->timer = $request['timer'];
            $activity->save();
        }

        return $activity;
    }

    public function nextActivity($courseId, $activityId)
    {
        $activities = Activity::where('course_id', $courseId)
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        $index = $activities->search(fn($activity) => $activity->id == $activityId);

        if ($index === false || $index >= $activities->count() - 1) {
            return null;
        }

        return $activities[$index + 1]->id;
    }

    // ActivityService.php
    public function getPreviousActivity($courseId, $currentActivityId)
    {
        $activities = Activity::where('course_id', $courseId)
            ->orderBy('position')
            ->orderBy('id')
            ->get();
        $index = $activities->search(function ($activity) use ($currentActivityId) {
            return $activity->id == $currentActivityId;
        });

        return $index > 0 ? $activities[$index - 1] : null;
    }
}
