<?php

namespace App\Http\Controllers\Instructor\Course\Question;

use App\Models\Course\Activity;
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

    public function create($slug)
    {
        return Inertia::render('Instructor/Course/PostTest/Create', [
            'slug' => $slug
        ]);
    }

    public function store(Request $request, Course $course)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'value' => 'required|numeric',
            'min_score' => 'required',
            'show_result' => 'boolean'
        ]);

        try {
            DB::beginTransaction();

            # Insert Activity
            $activity = $this->activity->createActivity([
                'course_id' => $course->id,
                'type' => 'post-test',
                'title' => $request->title,
                'value' => $request->value,
            ]);

            # Insert Pre Test
            $this->question->createQuestion([
                'activity_id' => $activity->id,
                'description' => $request->description,
                'type' => 'post',
                'min_score' => $request->min_score,
                'show_result' => $request->show_result
            ]);

            DB::commit();

            return to_route('instructor.course.show', $course->slug);
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);
            return to_route('instructor.course.show', $course->slug);
        }
    }

    public function edit($slug, Activity $post_test)
    {
        return Inertia::render('Instructor/Course/PostTest/Edit', [
            'slug' => $slug,
            'activity' => $this->question->getQuestion($post_test),
        ]);
    }

    public function update(Course $course, Activity $post_test, Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'value' => 'required|numeric',
            'min_score' => 'required|numeric'
        ]);

        try {
            DB::beginTransaction();

            # Update Activity
            $activity = $this->activity->updateActivity([
                'activity_id' => $post_test->id,
                'title' => $request->title,
                'value' => $request->value,
            ]);

            # Update Pre Test
            $this->question->updateQuestion([
                'activity_id' => $post_test->id,
                'type' => 'post',
                'description' => $request->description,
                'min_score' => $request->min_score,
                'show_result' => $request->show_result
            ]);

            DB::commit();

            return to_route('instructor.course.show', $course->slug);
        } catch (\Throwable $th) {
            DB::rollBack();
            dd($th);
            return to_route('instructor.course.show', $course->slug);
        }
    }
}
