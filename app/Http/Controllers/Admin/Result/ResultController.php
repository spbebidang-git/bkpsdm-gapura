<?php

namespace App\Http\Controllers\Admin\Result;

use App\Models\Identity\User;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\Course\Course;
use App\Models\Course\Activity\Activity;
use App\Services\Reporting\ResultService;

class ResultController
{
    protected $resultService;

    public function __construct(ResultService $resultService)
    {
        $this->resultService = $resultService;
    }

    public function index()
    {
        $courses = Course::all();

        return Inertia::render('Instructor/Result/Index', [
            'courses' => $courses
        ]);
    }

    public function show(Course $course, Request $request)
    {
        $filters = $request->only(['nama', 'kegiatan']);

        $results = $this->resultService->getRawResults($course->id, $filters)->map(function ($row) {
            $nilai = '-';
            if (in_array($row->activity_type, ['post-test', 'pre-test'])) {
                try {
                    $total = 0;
                    foreach (unserialize($row->response) as $item) {
                        $total += $item['score'];
                    }
                    $nilai = $total;
                } catch (\Exception $e) {
                    $nilai = 'Format Error';
                }
            }

            return [
                'nip' => $row->nip,
                'name' => $row->name,
                'jabatan' => $row->jabatan,
                'kegiatan' => $row->kegiatan,
                'nilai' => $nilai
            ];
        });
        // dd($results);
        $names = User::role('participant')->select('id', 'name')->get();
        $activities = Activity::where('course_id', $course->id)->get();

        return Inertia::render('Instructor/Result/Show', [
            'course' => $course,
            'results' => $results,
            'names' => $names,
            'activities' => $activities,
            'filters' => $filters,
        ]);
    }

    public function postTest(Course $course, Request $request)
    {
        $filters = $request->only(['nama', 'kegiatan']);
        $data = $this->resultService->getRawResults($course->id, $filters);
        $grouped = $this->resultService->groupAverageScores($data);

        return Inertia::render('Instructor/Result/PostTest', [
            'course' => $course,
            'results' => $grouped,
            'filters' => $filters,
        ]);
    }
}
