<?php

namespace App\Http\Controllers\Admin\Course\Question;

use App\Http\Requests\Admin\PreTestStoreRequest;
use App\Http\Requests\Admin\PreTestUpdateRequest;
use App\Models\Course\Activity\Activity;
use App\Models\Course\Course;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\Activity\AdminActivityService;
use App\Services\Course\Activity\QuestionService;
use Inertia\Inertia;

class PreTestController
{
    protected $activity;

    protected $question;

    public function __construct(
        ActivityService $activityService,
        QuestionService $questionService,
        private readonly AdminActivityService $management,
    ) {
        $this->activity = $activityService;
        $this->question = $questionService;
    }

    public function create(Course $course)
    {
        return Inertia::render('Instructor/Course/PreTest/Create', [
            'slug' => $course->slug,
        ]);
    }

    public function store(PreTestStoreRequest $request, Course $course)
    {
        try {
            $this->management->storePreTest($request->validated() + $request->only(['instruction', 'repeatable', 'show_result']), $course);

            return to_route('admin.course.activities.index', $course->slug)->with('success', 'Pre-Test berhasil ditambahkan.');
        } catch (\Throwable $th) {

            return to_route('admin.course.activities.index', $course->slug)->with('danger', 'Terjadi kesalahan saat menyimpan data.');
        }
    }

    public function edit(Course $course, Activity $pre_test)
    {
        return Inertia::render('Instructor/Course/PreTest/Edit', [
            'slug' => $course->slug,
            'activity' => $this->question->getQuestion($pre_test),
        ]);
    }

    public function update(Course $course, Activity $pre_test, PreTestUpdateRequest $request)
    {
        try {
            $this->management->updatePreTest($course, $pre_test, $request->validated() + $request->only(['instruction', 'repeatable', 'show_result']));

            return to_route('admin.course.activities.index', $course->slug)->with('success', 'Pre-Test berhasil diperbarui.');
        } catch (\Throwable $th) {

            return to_route('admin.course.activities.index', $course->slug)->with('danger', 'Terjadi kesalahan saat menyimpan data.');
        }
    }
}
