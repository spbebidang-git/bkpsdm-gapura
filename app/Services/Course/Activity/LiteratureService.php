<?php

namespace App\Services\Course\Activity;

use App\Helper\UploadMedia;
use App\Models\Course\Activity\Literature;
use App\Repositories\Course\Activity\Contracts\LiteratureRepositoryInterface;
use App\Services\Learning\LearnService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class LiteratureService
{
    public function __construct(
        private readonly LearnService $learn,
        private readonly LiteratureRepositoryInterface $literatures,
    ) {}

    public function createLiterature($data)
    {
        $directory = public_path(Literature::$directoryDocument);
        File::ensureDirectoryExists($directory);

        return $this->literatures->create([
            'activity_id' => $data['activity_id'],
            'sub_title' => $data['sub_title'],
            'duration' => $data['duration'],
            'content' => UploadMedia::file($data['content'], $directory),
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

        // Keep the previous PDF until the replacement has been uploaded and saved.
        if ($request['content']) {
            $oldFilePath = $literature->content ? public_path(Literature::$directoryDocument.$literature->content) : null;
            $directory = public_path(Literature::$directoryDocument);
            File::ensureDirectoryExists($directory);
            $literature->content = UploadMedia::file($request['content'], $directory);
        }

        $literature->save();
        if (isset($oldFilePath) && $oldFilePath) {
            DB::afterCommit(fn () => File::delete($oldFilePath));
        }

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
