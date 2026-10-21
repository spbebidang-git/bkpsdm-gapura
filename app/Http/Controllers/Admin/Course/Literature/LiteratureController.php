<?php

namespace App\Http\Controllers\Admin\Course\Literature;

use App\Models\Course\Activity\Activity;
use App\Models\Course\Activity\Literature;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use App\Models\Course\Course;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\Activity\LiteratureService;
use Illuminate\Validation\ValidationException;

class LiteratureController
{
    protected $activity, $literature;

    public function __construct(
        ActivityService $activityService,
        LiteratureService $literatureService
    ) {
        $this->activity = $activityService;
        $this->literature = $literatureService;
    }

    public function create(Course $course)
    {
        return Inertia::render('Instructor/Course/Literature/Create', [
            'slug' => $course->slug
        ]);
    }

    public function store(Request $request, Course $course)
    {
        $context = $this->uploadContext($request, $course);
        Log::channel('audit')->info('Permintaan unggah literatur diterima.', $context + ['event' => 'literature.store.received']);
        try {
            $request->validate([
                'title' => 'required',
                'sub_title' => 'required',
                'value' => 'required|numeric',
                'duration' => 'required|numeric|min:0.1',
                'content' => 'required|mimes:pdf|max:5120',
            ], [
                'duration.min' => 'Durasi membaca minimal 0,1 menit (6 detik).',
            ]);
        } catch (ValidationException $exception) {
            Log::channel('audit')->warning('Validasi unggah literatur gagal.', $context + [
                'event' => 'literature.store.validation_failed',
                'invalid_fields' => array_keys($exception->errors()),
            ]);
            throw $exception;
        }

        $stage = 'activity_insert';
        try {
            DB::beginTransaction();

            # Insert Activity
            $activity = $this->activity->createActivity([
                'course_id' => $course->id,
                'type' => 'literature',
                'title' => $request->title,
                'value' => $request->value,
                'instruction' => $request->instruction,
            ]);

            # Insert Literature
            $stage = 'pdf_upload';
            $this->literature->createLiterature([
                'activity_id' => $activity->id,
                'sub_title' => $request->sub_title,
                'content' => $request->content,
                'duration' => (int) round($request->duration * 60),
            ]);

            $stage = 'commit';
            DB::commit();
            Log::channel('audit')->info('Literatur berhasil disimpan.', $context + [
                'event' => 'literature.store.saved', 'activity_id' => $activity->id,
            ]);

            return to_route('admin.course.activities.index', $course->slug)->with('success', 'Literatur berhasil ditambahkan.');
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::channel('audit')->error('Penyimpanan literatur gagal.', $context + [
                'event' => 'literature.store.failed',
                'stage' => $stage,
                'exception' => $th,
            ]);

            return to_route('admin.course.activities.index', $course->slug)->with('danger', 'Terjadi kesalahan saat menyimpan data.');
        }
    }

    public function edit(Course $course, Activity $literature)
    {
        return Inertia::render('Instructor/Course/Literature/Edit', [
            'slug' => $course->slug,
            'activity' => $this->literature->getLiteratureForEdit($literature),
        ]);
    }

    public function update(Course $course, Activity $literature, Request $request)
    {
        $context = $this->uploadContext($request, $course) + ['activity_id' => $literature->id];
        Log::channel('audit')->info('Permintaan pembaruan literatur diterima.', $context + ['event' => 'literature.update.received']);
        try {
            $request->validate([
                'title' => 'required',
                'sub_title' => 'required',
                'value' => 'required|numeric',
                'content' => 'nullable|mimes:pdf|max:5120',
                'duration' => 'required|numeric|min:0.1',
            ], [
                'duration.min' => 'Durasi membaca minimal 0,1 menit (6 detik).',
            ]);
        } catch (ValidationException $exception) {
            Log::channel('audit')->warning('Validasi pembaruan literatur gagal.', $context + [
                'event' => 'literature.update.validation_failed',
                'invalid_fields' => array_keys($exception->errors()),
            ]);
            throw $exception;
        }

        $stage = 'activity_update';
        try {
            DB::beginTransaction();

            # Update Activity
            $activity = $this->activity->updateActivity([
                'activity_id' => $literature->id,
                'title' => $request->title,
                'value' => $request->value,
                'instruction' => $request->instruction,
            ]);

            # Update Pre Test
            $stage = 'pdf_upload';
            $this->literature->updateLiterature([
                'activity_id' => $activity->id,
                'sub_title' => $request->sub_title,
                'content' => $request->content,
                'duration' => (int) round($request->duration * 60),
            ]);

            $stage = 'commit';
            DB::commit();
            Log::channel('audit')->info('Literatur berhasil diperbarui.', $context + ['event' => 'literature.update.saved']);

            return to_route('admin.course.activities.index', $course->slug)->with('success', 'Literatur berhasil diperbarui.');
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::channel('audit')->error('Pembaruan literatur gagal.', $context + [
                'event' => 'literature.update.failed',
                'stage' => $stage,
                'exception' => $th,
            ]);
            return to_route('admin.course.activities.index', $course->slug)->with('danger', 'Terjadi kesalahan saat menyimpan data.');
        }
    }

    private function uploadContext(Request $request, Course $course): array
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
}
