<?php

namespace App\Services\Course\Activity;

use App\Helper\UploadMedia;
use App\Models\Course\Literature;
use App\Services\Learn\LearnService;

class LiteratureService
{
    protected $learn;

    public function __construct(LearnService $learnService)
    {
        $this->learn = $learnService;
    }

    public function createLiterature($data)
    {
        return Literature::create([
            'activity_id' => $data['activity_id'],
            'sub_title' => $data['sub_title'],
            'duration' => $data['duration'],
            'content' => UploadMedia::file($data['content'], Literature::$directoryDocument),
        ]);
    }

    public function getLiterature($activity)
    {
        $literature = Literature::where('activity_id', $activity->id)->first();

        return [
            'activity_id' => $activity->id,
            'title' => $activity->title,
            'type' => 'literature',
            'data' => [
                'duration' => $literature->duration,
                'src_pdf' => '/assets/media/documents/literatures/' . $literature->content
            ]
        ];
    }

    public function storeLiterature($participantId, $courseId, $activityId, $request)
    {
        $learnItem = [
            'type' => 'video',
            'status' => 'done',
            'response' => null
        ];

        $this->learn->createLearn($participantId, $courseId, $activityId, null, $learnItem);
    }

    public function updateLiterature($request)
    {
        $literature = Literature::where('activity_id', $request['activity_id'])->first();

        $literature->sub_title = $request['sub_title'];
        $literature->duration = $request['duration'];

        remove_file(Literature::$directoryDocument . $literature->content);
        $literature->content = UploadMedia::file($request['content'], Literature::$directoryDocument);
        $literature->save();

        return true;
    }

    public function getLiteratureForEdit($activity)
    {
        $literature = Literature::where('activity_id', $activity->id)->first();

        return [
            'id' => $activity->id,
            'title' => $activity->title,
            'value' => $activity->value,
            'sub_title' => $literature->sub_title,
            'duration' => $literature->duration,
        ];
    }
}
