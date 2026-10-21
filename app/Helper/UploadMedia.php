<?php

namespace App\Helper;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Symfony\Component\Filesystem\Path;

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

            $directory = Path::isAbsolute($path) ? $path : public_path($path);
            File::ensureDirectoryExists($directory);
            $uploadedFile->move($directory, Self::$file_name);

            return Self::$file_name;
        }
    }
}
