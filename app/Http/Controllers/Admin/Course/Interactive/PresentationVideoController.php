<?php

namespace App\Http\Controllers\Admin\Course\Interactive;

use App\Http\Requests\Admin\PresentationVideoStoreRequest;
use App\Http\Requests\Admin\PresentationVideoUpdateRequest;
use App\Models\Course\Activity\Activity;
use App\Models\Course\Course;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\Activity\AdminActivityService;
use App\Services\Course\Activity\InteractiveService;
use Inertia\Inertia;

class PresentationVideoController
{
    protected $activity;

    protected $interactive;

    public function __construct(
        ActivityService $activityService,
        InteractiveService $interactiveService,
        private readonly AdminActivityService $management,
    ) {
        $this->activity = $activityService;
        $this->interactive = $interactiveService;
    }

    public function create(Course $course)
    {
        return Inertia::render('Instructor/Course/VideoPresentation/Create', [
            'slug' => $course->slug,
        ]);
    }

    public function store(PresentationVideoStoreRequest $request, Course $course)
    {
        try {
            $this->management->storePresentationVideo($request->validated() + $request->only(['instruction', 'repeatable', 'show_result']), $course);

            return to_route('admin.course.activities.index', $course->slug)->with('success', 'Presentasi video berhasil ditambahkan.');
        } catch (\Throwable $th) {

            return to_route('admin.course.activities.index', $course->slug)->with('danger', 'Terjadi kesalahan saat menyimpan data.');
        }
    }

    public function edit(Course $course, Activity $video)
    {
        return Inertia::render('Instructor/Course/VideoPresentation/Edit', [
            'slug' => $course->slug,
            'activity' => $this->interactive->getInteractive($video),
        ]);
    }

    public function update(Course $course, Activity $video, PresentationVideoUpdateRequest $request)
    {
        try {
            $this->management->updatePresentationVideo($course, $video, $request->validated() + $request->only(['instruction', 'repeatable', 'show_result']));

            return to_route('admin.course.activities.index', $course->slug)->with('success', 'Presentasi video berhasil diperbarui.');
        } catch (\Throwable $th) {

            return to_route('admin.course.activities.index', $course->slug)->with('danger', 'Terjadi kesalahan saat menyimpan data.');
        }
    }
}
