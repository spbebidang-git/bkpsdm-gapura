<?php

namespace App\Http\Controllers\Instructor\Course\Question;

use App\Models\Course\Activity;
use App\Models\Course\Course;
use App\Services\Course\Activity\ActivityService;
use App\Services\Course\Activity\QuestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;


class PreTestController
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
        return Inertia::render('Instructor/Course/PreTest/Create', [
            'slug' => $slug
        ]);
    }

    public function store(Request $request, Course $course)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'instruction' => 'required|string',
            'value' => 'required|numeric',
            'timer' => 'required|numeric',
            'min_score' => 'required|numeric'
        ]);

        try {
            DB::beginTransaction();

            # Insert Activity
            $activity = $this->activity->createActivity([
                'course_id' => $course->id,
                'type' => 'pre-test',
                'title' => $request->title,
                'value' => $request->value,
                'timer' => $request->timer,
                'instruction' => $request->instruction,
            ]);

            # Insert Pre Test
            $this->question->createQuestion([
                'activity_id' => $activity->id,
                'description' => $request->description,
                'type' => 'pre',
                'min_score' => $request->min_score,
                'is_loop' => $request->repeatable
            ]);

            DB::commit();

            return to_route('instructor.course.show', $course->slug)->with('success', 'Pre-Test created successfully.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('instructor.course.show', $course->slug)->with('danger', 'Something went wrong.');
        }
    }

    public function edit($slug, Activity $pre_test)
    {
        return Inertia::render('Instructor/Course/PreTest/Edit', [
            'slug' => $slug,
            'activity' => $this->question->getQuestion($pre_test),
        ]);
    }

    public function update(Course $course, Activity $pre_test, Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'description' => 'required|string',
            'value' => 'required|numeric',
            'timer' => 'required|numeric'
        ]);

        try {
            DB::beginTransaction();

            # Update Activity
            $activity = $this->activity->updateActivity([
                'activity_id' => $pre_test->id,
                'title' => $request->title,
                'value' => $request->value,
                'timer' => $request->timer,
                'instruction' => $request->instruction,
            ]);

            # Update Pre Test
            $this->question->updateQuestion([
                'activity_id' => $pre_test->id,
                'type' => 'pre',
                'description' => $request->description,
                'min_score' => $request->min_score,
                'is_loop' => $request->repeatable
            ]);

            DB::commit();

            return to_route('instructor.course.show', $course->slug)->with('success', 'Pre-Test updated successfully.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('instructor.course.show', $course->slug)->with('danger', 'Something went wrong.');
        }
    }
}
