<?php

namespace App\Http\Controllers\Admin\Course;

use App\Models\Course\Course;
use App\Services\Course\Activity\ActivityService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ActivityController
{
    public function __construct(private readonly ActivityService $activities) {}

    public function management(Request $request, Course $course)
    {
        return Inertia::render('Admin/Course/Activities', [
            'course' => ['id' => $course->id, 'slug' => $course->slug, 'title' => $course->title, 'type' => $course->type, 'is_locked' => (bool) $course->is_locked],
            'canUnlock' => $request->user()?->hasRole('super_admin') ?? false,
            'activities' => $this->activities->getActivityByCourse($course->id),
        ]);
    }

    public function index(Request $request, Course $course)
    {
        return Inertia::render('Instructor/Course/Activity', [
            'slug' => $course->slug,
            'type' => $course->type,
        ]);
    }

    public function destroy(Course $course, $activityId)
    {
        $activity = $this->activities->findForCourse($course->id, $activityId);
        try {
            $this->activities->deleteActivity($activity);

            return to_route('admin.course.activities.index', $course->slug)->with('success', 'Aktivitas berhasil dihapus.');
        } catch (\Throwable $th) {

            return to_route('admin.course.activities.index', $course->slug)->with('danger', 'Aktivitas gagal dihapus.');
        }
    }
}
