<?php

namespace App\Helper;

use Illuminate\Http\UploadedFile;

class UploadMedia
{
    public static $file_name;

    public static function file(UploadedFile $uploadedFile, $path, $filename = null)
    {
        if (!empty($uploadedFile)) {
            if ($filename == null) {
                Self::$file_name = random_filename($uploadedFile);
            } else {
                Self::$file_name = $filename . '.' . $uploadedFile->getClientOriginalExtension();
            }

            $uploadedFile->move($path, Self::$file_name);

            return Self::$file_name;
        }
    }
}
