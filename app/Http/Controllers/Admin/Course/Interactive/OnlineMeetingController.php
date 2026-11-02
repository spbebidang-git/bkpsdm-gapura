<?php

namespace App\Http\Controllers\Admin\Course\Interactive;

use App\Http\Requests\Admin\OnlineMeetingStoreRequest;
use App\Http\Requests\Admin\OnlineMeetingUpdateRequest;
use App\Models\Course\Activity\Activity;
use App\Models\Course\Course;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\Activity\AdminActivityService;
use App\Services\Course\Activity\InteractiveService;
use Inertia\Inertia;

class OnlineMeetingController
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
        return Inertia::render('Instructor/Course/Meeting/Create', [
            'slug' => $course->slug,
        ]);
    }

    public function store(OnlineMeetingStoreRequest $request, Course $course)
    {
        try {
            $this->management->storeOnlineMeeting($request->validated() + $request->only(['instruction', 'repeatable', 'show_result']), $course);

            return to_route('admin.course.activities.index', $course->slug)->with('success', 'Pertemuan daring berhasil ditambahkan.');
        } catch (\Throwable $th) {

            return to_route('admin.course.activities.index', $course->slug)->with('danger', 'Terjadi kesalahan saat menyimpan data.');
        }
    }

    public function edit(Course $course, Activity $meeting)
    {
        return Inertia::render('Instructor/Course/Meeting/Edit', [
            'slug' => $course->slug,
            'activity' => $this->interactive->getInteractive($meeting),
        ]);
    }

    public function update(Course $course, Activity $meeting, OnlineMeetingUpdateRequest $request)
    {
        try {
            $this->management->updateOnlineMeeting($course, $meeting, $request->validated() + $request->only(['instruction', 'repeatable', 'show_result']));

            return to_route('admin.course.activities.index', $course->slug)->with('success', 'Pertemuan daring berhasil diperbarui.');
        } catch (\Throwable $th) {

            return to_route('admin.course.activities.index', $course->slug)->with('danger', 'Terjadi kesalahan saat menyimpan data.');
        }
    }

    public function endMeeting(Course $course, $activityId)
    {
        try {
            $this->management->endMeetingOnlineMeeting($course, $activityId);

            return to_route('admin.course.activities.index', $course->slug)->with('success', 'Pertemuan daring berhasil diakhiri.');
        } catch (\Throwable $th) {

            return to_route('admin.course.activities.index', $course->slug)->with('danger', 'Terjadi kesalahan saat menyimpan data.');
        }
    }
}
