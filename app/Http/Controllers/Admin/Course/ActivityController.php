<?php

namespace App\Http\Controllers\Admin\Course;

use App\Models\Course\Activity\Activity;
use App\Models\Course\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ActivityController
{
    public function management(Request $request, Course $course)
    {
        return Inertia::render('Admin/Course/Activities', [
            'course' => ['id' => $course->id, 'slug' => $course->slug, 'title' => $course->title, 'type' => $course->type, 'is_locked' => (bool) $course->is_locked],
            'canUnlock' => $request->user()?->hasRole('super_admin') ?? false,
            'activities' => app(\App\Services\Course\Activity\ActivityService::class)->getActivityByCourse($course->id),
        ]);
    }

    public function index(Request $request, Course $course)
    {
        return Inertia::render('Instructor/Course/Activity', [
            'slug' => $course->slug,
            'type' => $course->type
        ]);
    }

    public function destroy(Course $course, $activityId)
    {
        $activity = Activity::query()->where('course_id', $course->id)->findOrFail($activityId);
        try {
            DB::beginTransaction();
            $activity->delete();

            DB::commit();

            return to_route('admin.course.activities.index', $course->slug)->with('success', 'Aktivitas berhasil dihapus.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return to_route('admin.course.activities.index', $course->slug)->with('danger', 'Aktivitas gagal dihapus.');
        }
    }
}
