<?php

namespace App\Http\Controllers\Instructor\Course\Literature;

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

    public function create($slug)
    {
        return Inertia::render('Instructor/Course/Literature/Create', [
            'slug' => $slug
        ]);
    }

    public function store(Request $request, Course $course)
    {
        // dd($request->all());
        $request->validate([
            'title' => 'required',
            'sub_title' => 'required',
            'content' => 'required|mimes:pdf|max:1024',
        ]);

        try {
            DB::beginTransaction();

            # Insert Activity
            $activity = $this->activity->createActivity([
                'course_id' => $course->id,
                'type' => 'literature',
                'title' => $request->title,
            ]);

            # Insert Literature
            $this->literature->createLiterature([
                'activity_id' => $activity->id,
                'sub_title' => $request->sub_title,
                'content' => $request->content,
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
