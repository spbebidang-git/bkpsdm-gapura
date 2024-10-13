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
}
