<?php

namespace App\Http\Controllers\Admin\Course\Literature;

use App\Http\Requests\Admin\LiteratureStoreRequest;
use App\Http\Requests\Admin\LiteratureUpdateRequest;
use App\Models\Course\Activity\Activity;
use App\Models\Course\Course;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\Activity\AdminActivityService;
use App\Services\Course\Activity\LiteratureService;
use App\Services\Course\Activity\LiteratureUploadAuditService;
use Inertia\Inertia;

class LiteratureController
{
    protected $activity;

    protected $literature;

    public function __construct(
        ActivityService $activityService,
        LiteratureService $literatureService,
        private readonly AdminActivityService $management,
        private readonly LiteratureUploadAuditService $uploadAudit,
    ) {
        $this->activity = $activityService;
        $this->literature = $literatureService;
    }

    public function create(Course $course)
    {
        return Inertia::render('Instructor/Course/Literature/Create', [
            'slug' => $course->slug,
        ]);
    }

    public function store(LiteratureStoreRequest $request, Course $course)
    {
        try {
            $this->management->storeLiterature($request->validated() + $request->only(['instruction']), $course, $this->uploadAudit->context($request, $course));

            return to_route('admin.course.activities.index', $course->slug)->with('success', 'Literatur berhasil ditambahkan.');
        } catch (\Throwable $th) {
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

    public function update(Course $course, Activity $literature, LiteratureUpdateRequest $request)
    {
        try {
            $this->management->updateLiterature($course, $literature, $request->validated() + $request->only(['instruction']), $this->uploadAudit->context($request, $course));

            return to_route('admin.course.activities.index', $course->slug)->with('success', 'Literatur berhasil diperbarui.');
        } catch (\Throwable $th) {
            return to_route('admin.course.activities.index', $course->slug)->with('danger', 'Terjadi kesalahan saat menyimpan data.');
        }
    }
}
