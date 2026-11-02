<?php

namespace App\Services\Course\Activity;

use App\Models\Course\Activity\Literature;
use App\Models\Course\Course;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class LiteratureUploadAuditService
{
    public function context(Request $request, Course $course): array
    {
        $file = $request->file('content');
        $uploadError = $file instanceof UploadedFile ? $file->getError() : null;
        $directory = public_path(Literature::$directoryDocument);
        $parent = $directory;
        while (! is_dir($parent) && dirname($parent) !== $parent) {
            $parent = dirname($parent);
        }

        return [
            'request_id' => $request->attributes->get('request_id'),
            'actor_id' => $request->user()?->getAuthIdentifier(),
            'course_id' => $course->id,
            'file_received' => $file instanceof UploadedFile,
            'upload_error_code' => $uploadError,
            'upload_error_name' => match ($uploadError) {
                UPLOAD_ERR_OK => 'ok',
                UPLOAD_ERR_INI_SIZE => 'exceeds_upload_max_filesize',
                UPLOAD_ERR_FORM_SIZE => 'exceeds_form_max_file_size',
                UPLOAD_ERR_PARTIAL => 'partial_upload',
                UPLOAD_ERR_NO_FILE => 'no_file',
                UPLOAD_ERR_NO_TMP_DIR => 'temporary_directory_missing',
                UPLOAD_ERR_CANT_WRITE => 'temporary_directory_unwritable',
                UPLOAD_ERR_EXTENSION => 'blocked_by_php_extension',
                default => null,
            },
            'upload_size_bytes' => $file instanceof UploadedFile && $file->isValid() ? $file->getSize() : null,
            'allowed_pdf_max_bytes' => 5 * 1024 * 1024,
            'content_length_bytes' => (int) $request->server('CONTENT_LENGTH', 0),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'target_directory' => $directory,
            'target_exists' => is_dir($directory),
            'target_writable' => is_dir($directory) ? is_writable($directory) : null,
            'nearest_existing_directory' => $parent,
            'nearest_existing_directory_writable' => is_writable($parent),
        ];
    }

    public function received(Request $request, string $action): void
    {
        $context = $this->context($request, $request->route('course'));
        if ($action === 'update') {
            $context['activity_id'] = $request->route('literature')->id;
        }
        Log::channel('audit')->info($action === 'store' ? 'Permintaan unggah literatur diterima.' : 'Permintaan pembaruan literatur diterima.', $context + ['event' => "literature.{$action}.received"]);
    }

    public function validationFailed(Request $request, string $action, array $errors): void
    {
        $context = $this->context($request, $request->route('course'));
        if ($action === 'update') {
            $context['activity_id'] = $request->route('literature')->id;
        }
        Log::channel('audit')->warning($action === 'store' ? 'Validasi unggah literatur gagal.' : 'Validasi pembaruan literatur gagal.', $context + ['event' => "literature.{$action}.validation_failed", 'invalid_fields' => array_keys($errors)]);
    }
}
