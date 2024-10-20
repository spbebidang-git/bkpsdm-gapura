<?php

namespace App\Services\Course\Activity;

use App\Helper\UploadMedia;
use App\Models\Course\Literature;

class LiteratureService
{
    public function createLiterature($data)
    {
        return Literature::create([
            'activity_id' => $data['activity_id'],
            'sub_title' => $data['sub_title'],
            'content' => UploadMedia::file($data['content'], Literature::$directoryDocument),
        ]);
    }

    public function getLiterature()
    {
        return [
            'activity_id' => 10,
            'type' => 'literature',
            'data' => [
                'src_pdf' => Literature::$directoryDocument . 'BIWide-EES2023-Pure.pdf'
            ]
        ];
    }

    public function storeLiterature() {}

    public function updateLiterature($request)
    {
        $literature = Literature::where('activity_id', $request['activity_id'])->first();

        $literature->sub_title = $request['sub_title'];

        remove_file(Literature::$directoryDocument);
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
        ];
    }
}
