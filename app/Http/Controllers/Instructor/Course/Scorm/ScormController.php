<?php

namespace App\Http\Controllers\Instructor\Course\Scorm;

use App\Models\Course\Activity;
use App\Models\Course\Course;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\Activity\ScormService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ScormController
{
    protected $activity, $scorm;

    public function __construct(ActivityService $activityService, ScormService $scormService)
    {
        $this->activity = $activityService;
        $this->scorm = $scormService;
    }

    public function create(Course $course)
    {
        return Inertia::render('Instructor/Course/Scorm/Create', [
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
                'type'        => 'scorm',
                'title'       => $request->title,
                'value'       => $request->value,
                'instruction' => $request->instruction,
            ]);

            $this->scorm->createScorm([
                'activity_id' => $activity->id,
                'sub_title'   => $request->sub_title,
                'duration'    => $request->duration,
                'package'     => $request->file('package'),
            ]);

            DB::commit();

            return to_route('instructor.course.show', $course->slug)
                ->with('success', 'Konten SCORM berhasil ditambahkan.');
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('SCORM store failed: ' . $th->getMessage(), ['trace' => $th->getTraceAsString()]);
            return to_route('instructor.course.show', $course->slug)
                ->with('danger', 'Gagal menambahkan SCORM: ' . $th->getMessage());
        }
    }

    public function edit(Course $course, Activity $scorm)
    {
        return Inertia::render('Instructor/Course/Scorm/Edit', [
            'slug'     => $course->slug,
            'activity' => $this->scorm->getScormForEdit($scorm),
        ]);
    }

    public function update(Request $request, Course $course, Activity $scorm)
    {
        $this->validatePackage($request, false);

        try {
            DB::beginTransaction();

            $activity = $this->activity->updateActivity([
                'activity_id' => $scorm->id,
                'title'       => $request->title,
                'value'       => $request->value,
                'instruction' => $request->instruction,
            ]);

            $this->scorm->updateScorm([
                'activity_id' => $activity->id,
                'sub_title'   => $request->sub_title,
                'duration'    => $request->duration,
                'package'     => $request->file('package'),
            ]);

            DB::commit();

            return to_route('instructor.course.show', $course->slug)
                ->with('success', 'Konten SCORM berhasil diperbarui.');
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('SCORM update failed: ' . $th->getMessage(), ['trace' => $th->getTraceAsString()]);
            return to_route('instructor.course.show', $course->slug)
                ->with('danger', 'Gagal memperbarui SCORM: ' . $th->getMessage());
        }
    }

    protected function validatePackage(Request $request, bool $required = true): void
    {
        $request->validate([
            'title'     => 'required|string',
            'sub_title' => 'nullable|string',
            'value'     => 'required|numeric',
            'duration'  => 'required|numeric',
            'package'   => ($required ? 'required|' : 'nullable|') . 'file|max:307200', // maks 300MB
        ]);

        $file = $request->file('package');
        if ($file && strtolower($file->getClientOriginalExtension()) !== 'zip') {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'package' => 'Paket SCORM harus berupa file .zip',
            ]);
        }
    }
}
