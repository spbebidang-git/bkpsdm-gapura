<?php

namespace App\Http\Controllers\Admin\Course\Question;

use App\Http\Requests\Admin\PostTestStoreRequest;
use App\Http\Requests\Admin\PostTestUpdateRequest;
use App\Models\Course\Activity\Activity;
use App\Models\Course\Course;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\Activity\AdminActivityService;
use App\Services\Course\Activity\QuestionService;
use Inertia\Inertia;

class PostTestController
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
        return Inertia::render('Instructor/Course/PostTest/Create', [
            'slug' => $course->slug,
        ]);
    }

    public function store(PostTestStoreRequest $request, Course $course)
    {
        try {
            $this->management->storePostTest($request->validated() + $request->only(['instruction', 'is_loop']), $course);

            return to_route('admin.course.activities.index', $course->slug)->with('success', 'Post-Test berhasil ditambahkan.');
        } catch (\Throwable $th) {

            return to_route('admin.course.activities.index', $course->slug)->with('danger', 'Terjadi kesalahan saat menyimpan data.');
        }
    }

    public function edit(Course $course, Activity $post_test)
    {
        return Inertia::render('Instructor/Course/PostTest/Edit', [
            'slug' => $course->slug,
            'activity' => $this->question->getQuestion($post_test),
        ]);
    }

    public function update(Course $course, Activity $post_test, PostTestUpdateRequest $request)
    {

        try {
            $this->management->updatePostTest($course, $post_test, $request->validated() + $request->only(['instruction', 'is_loop']));

            return to_route('admin.course.activities.index', $course->slug)->with('success', 'Post-Test berhasil diperbarui.');
        } catch (\Throwable $th) {

            return to_route('admin.course.activities.index', $course->slug)->with('danger', 'Terjadi kesalahan saat menyimpan data.');
        }
    }
}
