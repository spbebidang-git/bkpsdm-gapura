<?php

namespace App\Http\Controllers\Instructor\Course;

use App\Models\Course\Activity;
use App\Models\Course\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ActivityController
{
    public function index(Request $request, Course $course)
    {
        return Inertia::render('Instructor/Course/Activity', [
            'slug' => $course->slug,
            'type' => $course->type
        ]);
    }

    public function destroy(Course $course, $activityId)
    {
        try {
            DB::beginTransaction();

            $activity = Activity::find($activityId);

            $activity->delete();

            DB::commit();

            return to_route('instructor.course.show', $course->slug)->with('success', 'Activity deleted successfully.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('instructor.course.show', $course->slug)->with('danger', 'Something went wrong.');
        }
    }
}
