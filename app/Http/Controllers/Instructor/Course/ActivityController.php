<?php

namespace App\Http\Controllers\Instructor\Course;

use App\Models\Course\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ActivityController
{
    public function index(Request $request, $slug)
    {
        return Inertia::render('Instructor/Course/Activity', [
            'slug' => $slug
        ]);
    }

    public function destroy($slug, $activityId)
    {
        try {
            DB::beginTransaction();

            $activity = Activity::find($activityId);

            $activity->delete();

            DB::commit();

            return to_route('instructor.course.show', $slug)->with('success', 'Activity deleted successfully.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('instructor.course.show', $slug)->with('danger', 'Something went wrong.');
        }
    }
}
