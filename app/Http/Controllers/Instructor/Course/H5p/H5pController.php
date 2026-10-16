<?php

namespace App\Http\Controllers\Instructor\Course\H5p;

use App\Models\Course\Activity;
use App\Models\Course\Course;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\Activity\H5pService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class H5pController
{
    protected $activity, $h5p;

    public function __construct(ActivityService $activityService, H5pService $h5pService)
    {
        $this->activity = $activityService;
        $this->h5p = $h5pService;
    }

    public function create(Course $course)
    {
        return Inertia::render('Instructor/Course/H5p/Create', [
            'slug' => $course->slug,
        ]);
    }

    public function store(Request $request, Course $course)
    {
        $this->validatePackage($request);

        try {
            DB::beginTransaction();

            $activity = $this->activity->createActivity([
                'course_id'   => $course->id,
                'type'        => 'h5p',
                'title'       => $request->title,
                'value'       => $request->value,
                'instruction' => $request->instruction,
            ]);

            $this->h5p->createH5p([
                'activity_id' => $activity->id,
                'sub_title'   => $request->sub_title,
                'duration'    => $request->duration,
                'package'     => $request->file('package'),
            ]);

            DB::commit();

            return to_route('instructor.course.show', $course->slug)
                ->with('success', 'Konten H5P berhasil ditambahkan.');
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('H5P store failed: ' . $th->getMessage(), ['trace' => $th->getTraceAsString()]);
            return to_route('instructor.course.show', $course->slug)
                ->with('danger', 'Gagal menambahkan H5P: ' . $th->getMessage());
        }
    }

    public function edit(Course $course, Activity $h5p)
    {
        return Inertia::render('Instructor/Course/H5p/Edit', [
            'slug'     => $course->slug,
            'activity' => $this->h5p->getH5pForEdit($h5p),
        ]);
    }

    public function update(Request $request, Course $course, Activity $h5p)
    {
        // Aktivitas placeholder hasil impor Moodle wajib dilengkapi paket pada
        // penyimpanan pertama. Aktivitas yang sudah siap boleh tetap memakai
        // paket lama.
        $this->validatePackage($request, !$this->h5p->hasPackage($h5p->id));

        try {
            DB::beginTransaction();

            $activity = $this->activity->updateActivity([
                'activity_id' => $h5p->id,
                'title'       => $request->title,
                'value'       => $request->value,
                'instruction' => $request->instruction,
            ]);

            $this->h5p->updateH5p([
                'activity_id' => $activity->id,
                'sub_title'   => $request->sub_title,
                'duration'    => $request->duration,
                'package'     => $request->file('package'),
            ]);

            DB::commit();

            return to_route('instructor.course.show', $course->slug)
                ->with('success', 'Konten H5P berhasil diperbarui.');
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('H5P update failed: ' . $th->getMessage(), ['trace' => $th->getTraceAsString()]);
            return to_route('instructor.course.show', $course->slug)
                ->with('danger', 'Gagal memperbarui H5P: ' . $th->getMessage());
        }
    }

    /**
     * Validasi field + paket .h5p. $required=false untuk update (paket opsional).
     */
    protected function validatePackage(Request $request, bool $required = true): void
    {
        $request->validate([
            'title'     => 'required|string',
            'sub_title' => 'nullable|string',
            'value'     => 'required|numeric',
            'duration'  => 'required|numeric',
            'package'   => ($required ? 'required|' : 'nullable|') . 'file|max:204800', // maks 200MB
        ]);

        $file = $request->file('package');
        if ($file && strtolower($file->getClientOriginalExtension()) !== 'h5p') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'package' => 'File harus berekstensi .h5p',
            ]);
        }
    }
}
