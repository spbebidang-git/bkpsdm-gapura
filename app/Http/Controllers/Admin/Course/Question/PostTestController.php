<?php

namespace App\Http\Controllers\Admin\Course\Question;

use App\Models\Course\Activity\Activity;
use App\Models\Course\Course;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\Activity\QuestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PostTestController
{
    protected $activity, $question;

    public function __construct(
        ActivityService $activityService,
        QuestionService $questionService
    ) {
        $this->activity = $activityService;
        $this->question = $questionService;
    }

    public function create(Course $course)
    {
        return Inertia::render('Instructor/Course/PostTest/Create', [
            'slug' => $course->slug
        ]);
    }

    public function store(Request $request, Course $course)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'value' => 'required|numeric',
            'timer' => 'required|numeric|min:1',
            'min_score' => 'required|numeric',
            'is_loop' => 'boolean'
        ]);

        try {
            DB::beginTransaction();

            # Insert Activity
            $activity = $this->activity->createActivity([
                'course_id' => $course->id,
                'type' => 'post-test',
                'title' => $request->title,
                'value' => $request->value,
                'timer' => $request->timer * 60,
                'instruction' => $request->instruction,
            ]);

            # Insert Pre Test
            $this->question->createQuestion([
                'activity_id' => $activity->id,
                'description' => $request->description,
                'type' => 'post',
                'min_score' => $request->min_score,
                'is_loop' => $request->is_loop
            ]);

            DB::commit();

            return to_route('admin.course.activities.index', $course->slug)->with('success', 'Post-Test berhasil ditambahkan.');
        } catch (\Throwable $th) {
            DB::rollBack();
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

    public function update(Course $course, Activity $post_test, Request $request)
    {
        // dd($request->all());
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'value' => 'required|numeric',
            'timer' => 'required|numeric|min:1',
            'min_score' => 'required|numeric'
        ]);

        try {
            DB::beginTransaction();

            # Update Activity
            $activity = $this->activity->updateActivity([
                'activity_id' => $post_test->id,
                'title' => $request->title,
                'value' => $request->value,
                'timer' => $request->timer * 60,
                'instruction' => $request->instruction,
            ]);

            # Update Pre Test
            $this->question->updateQuestion([
                'activity_id' => $post_test->id,
                'type' => 'post',
                'description' => $request->description,
                'min_score' => $request->min_score,
                'is_loop' => $request->is_loop
            ]);

            DB::commit();

            return to_route('admin.course.activities.index', $course->slug)->with('success', 'Post-Test berhasil diperbarui.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('admin.course.activities.index', $course->slug)->with('danger', 'Terjadi kesalahan saat menyimpan data.');
        }
    }
}
