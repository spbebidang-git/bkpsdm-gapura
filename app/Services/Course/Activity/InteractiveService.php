<?php

namespace App\Services\Course\Activity;

use App\Models\Course\Activity;
use App\Models\Course\Interactive;
use App\Models\Learn\ActivityRuntimeState;
use App\Models\Learn\Learn;
use App\Services\Learn\LearnService;

class InteractiveService
{
    protected $learn;

    public function __construct(LearnService $learnService)
    {
        $this->learn = $learnService;
    }

    public function createInteractive($data)
    {
        return Interactive::create($data);
    }

    public function getMeeting($activity)
    {
        return [
            'activity_id' => $activity->id,
            'type' => 'meeting',
            'data' => []
        ];
    }

    public function getStatus($participantId, $activityId, $courseId = null)
    {
        $learn = Learn::with(['learnItems' => function ($query) use ($activityId) {
            return $query->where('activity_id', $activityId);
        }])
            ->where('participant_id', $participantId)
            ->when($courseId !== null, fn ($query) => $query->where('course_id', $courseId))
            ->first();

        if (!$learn || !$learn->learnItems->first()) {
            return 0;
        }

        return $learn->learnItems->first()->status;
    }

    public function getMeetingParticipant($participantId, $activity)
    {
        $interactive = Interactive::where('activity_id', $activity->id)->first();
        $learn = Learn::with(['learnItems' => function ($query) use ($activity) {
            return $query->where('activity_id', $activity->id);
        }])
            ->where('participant_id', $participantId)
            ->where('course_id', $activity->course_id)
            ->first();
        $status = null;
        if (!$learn || !$learn->learnItems->first()) {
            $status = 0;
        } else {
            $status = $learn->learnItems->first()->status;
        }

        return [
            'activity_id' => $activity->id,
            'title' => $activity->title,
            'type' => 'meeting',
            'status' => $status,
            'data' => [
                'meeting_url' => $interactive->url,
                'duration' => $interactive->duration
            ]
        ];
    }

    public function getVideo($activity, ?int $participantId = null)
    {
        $interactive = Interactive::where('activity_id', $activity->id)->first();
        $status = $participantId === null
            ? 0
            : $this->getStatus($participantId, $activity->id, $activity->course_id);

        return [
            'activity_id' => $activity->id,
            'title' => $activity->title,
            'type' => 'video',
            'status' => $status,
            'data' => [
                'src_video' => $interactive->url,
                'duration' => $interactive->duration,
                'resume' => $participantId === null
                    ? null
                    : $this->videoResume($participantId, $activity),
            ]
        ];
    }

    /**
     * Posisi tonton terakhir peserta (seperti resume H5P/SCORM) agar video
     * tidak kembali ke 0 ketika player ditutup lalu dibuka lagi.
     */
    protected function videoResume(int $participantId, $activity): ?array
    {
        $learn = Learn::where('participant_id', $participantId)
            ->where('course_id', $activity->course_id)
            ->first();

        if (!$learn) {
            return null;
        }

        $runtime = ActivityRuntimeState::where('learn_id', $learn->id)
            ->where('activity_id', $activity->id)
            ->first();

        $state = is_array($runtime?->state) ? $runtime->state : [];

        if (empty($state)) {
            return null;
        }

        return [
            'position' => (float) ($state['position'] ?? 0),
            'furthest' => (float) ($state['furthest'] ?? 0),
        ];
    }

    public function storeMeeting($participantId, $courseId, $activityId, $request)
    {
        $interactive = Interactive::where('activity_id', $activityId)->first();


        if ($request) {
            $learnItem = [
                'type' => 'meeting',
                'status' => $request['status'],
                'response' => null
            ];
        } else {
            $learnItem = [
                'type' => 'meeting',
                'status' => 'done',
                'response' => null
            ];
        }

        if ($request && $request['status'] == 'done') {
            if (!$interactive->end_meeting) {
                return false;
            }
        }

        $this->learn->createLearn($participantId, $courseId, $activityId, null, $learnItem);

        return true;
    }

    public function storeVideo($participantId, $courseId, $activityId, $request)
    {
        $learnItem = [
            'type' => 'video',
            'status' => 'done',
            'response' => null
        ];

        $this->learn->createLearn($participantId, $courseId, $activityId, null, $learnItem);
    }

    public function updateInteractive($request)
    {
        $interactive = Interactive::where('activity_id', $request['activity_id'])->first();

        $interactive->update([
            'description' => $request['description'],
            'url' => $request['url'],
            'duration' => $request['duration'],
        ]);

        return true;
    }

    public function getInteractive($activity)
    {
        $interactive = Interactive::where('activity_id', $activity->id)->first();

        return [
            'id' => $activity->id,
            'title' => $activity->title,
            'value' => $activity->value,
            'instruction' => $activity->instruction,
            'description' => $interactive->description,
            'url' => $interactive->url,
            'duration' => $interactive->duration,
        ];
    }
}
