<?php

namespace App\Services\Course\Activity;

use App\Helper\UploadMedia;
use App\Models\Course\Activity\Literature;
use App\Repositories\Course\Activity\Contracts\LiteratureRepositoryInterface;
use App\Services\Learning\LearnService;

class LiteratureService
{
    public function __construct(
        private readonly LearnService $learn,
        private readonly LiteratureRepositoryInterface $literatures,
    ) {}

    public function createLiterature($data)
    {
        return $this->literatures->create([
            'activity_id' => $data['activity_id'],
            'sub_title' => $data['sub_title'],
            'duration' => $data['duration'],
            'content' => UploadMedia::file($data['content'], Literature::$directoryDocument),
        ]);
    }

    public function getLiterature($activity)
    {
        $literature = $this->literatures->findByActivity($activity->id);

        return [
            'activity_id' => $activity->id,
            'title' => $activity->title,
            'type' => 'literature',
            'data' => [
                'duration' => $literature->duration,
                'src_pdf' => '/assets/media/documents/literatures/'.$literature->content,
            ],
        ];
    }

    public function storeLiterature($participantId, $courseId, $activityId, $request)
    {
        $learnItem = [
            'type' => 'literature',
            'status' => 'done',
            'response' => null,
        ];

        $this->learn->createLearn($participantId, $courseId, $activityId, $learnItem);
    }

    public function updateLiterature($request)
    {
        $literature = $this->literatures->findByActivity($request['activity_id']);

        $literature->sub_title = $request['sub_title'];
        $literature->duration = $request['duration'];

        // Hanya hapus jika ada file baru di-upload
        if ($request['content']) {

            // Cek file lama ada atau tidak
            $oldFilePath = Literature::$directoryDocument.$literature->content;

            if ($literature->content && file_exists(public_path($oldFilePath))) {
                @unlink(public_path($oldFilePath));
            }

            // Upload file baru
            $literature->content = UploadMedia::file($request['content'], Literature::$directoryDocument);
        }

        $literature->save();

        return true;
    }

    public function getLiteratureForEdit($activity)
    {
        $literature = $this->literatures->findByActivity($activity->id);

        return [
            'id' => $activity->id,
            'title' => $activity->title,
            'value' => $activity->value,
            'instruction' => $activity->instruction,
            'sub_title' => $literature?->sub_title ?? '',
            'duration' => $literature?->duration ?? 0,
            'content' => $literature?->content,
            'content_url' => $literature?->content
                ? asset(Literature::$directoryDocument.$literature->content)
                : null,
        ];
    }
}
