<?php

namespace App\Http\Controllers\Admin\Course\Literature;

use App\Models\Course\Activity\Activity;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Course\Course;
use Illuminate\Support\Facades\DB;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\Activity\LiteratureService;

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
        $request->validate([
            'title' => 'required',
            'sub_title' => 'required',
            'value' => 'required|numeric',
            'duration' => 'required|numeric|min:0.1',
            'content' => 'required|mimes:pdf|max:5120',
        ], [
            'duration.min' => 'Durasi membaca minimal 0,1 menit (6 detik).',
        ]);

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
            $this->literature->createLiterature([
                'activity_id' => $activity->id,
                'sub_title' => $request->sub_title,
                'content' => $request->content,
                'duration' => (int) round($request->duration * 60),
            ]);

            DB::commit();

            return to_route('admin.course.activities.index', $course->slug)->with('success', 'Literatur berhasil ditambahkan.');
        } catch (\Throwable $th) {
            DB::rollBack();

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
        $request->validate([
            'title' => 'required',
            'sub_title' => 'required',
            'value' => 'required|numeric',
            'content' => 'nullable|mimes:pdf|max:5120',
            'duration' => 'required|numeric|min:0.1',
        ], [
            'duration.min' => 'Durasi membaca minimal 0,1 menit (6 detik).',
        ]);

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
            $this->literature->updateLiterature([
                'activity_id' => $activity->id,
                'sub_title' => $request->sub_title,
                'content' => $request->content,
                'duration' => (int) round($request->duration * 60),
            ]);

            DB::commit();

            return to_route('admin.course.activities.index', $course->slug)->with('success', 'Literatur berhasil diperbarui.');
        } catch (\Throwable $th) {
            DB::rollBack();
            return to_route('admin.course.activities.index', $course->slug)->with('danger', 'Terjadi kesalahan saat menyimpan data.');
        }
    }
}
