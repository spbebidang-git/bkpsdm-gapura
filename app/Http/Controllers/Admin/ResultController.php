<?php

namespace App\Http\Controllers\Admin;

use App\Models\Course\Activity\Activity;
use App\Models\Course\Course;
use App\Models\Identity\User;
use App\Services\Reporting\ResultService;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class ResultController
{
    public function __construct(protected ResultService $resultService) {}

    public function index()
    {
        if (request()->ajax() && ! request()->header('X-Inertia')) {
            return DataTables::of(Course::query()->select('id', 'title', 'slug', 'updated_at'))
                ->addIndexColumn()
                ->make();
        }

        return Inertia::render('Admin/Result/Index');
    }

    public function show(Course $course)
    {
        $filters = request()->only(['nama', 'kegiatan']);
        $data = $this->resultService->getRawResults($course->id, $filters);

        if (request()->ajax() && ! request()->header('X-Inertia')) {
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('nip', fn ($row) => $row->nip)
                ->addColumn('nama', fn ($row) => $row->name)
                ->addColumn('jabatan', fn ($row) => $row->jabatan)
                ->addColumn('kegiatan', fn ($row) => $row->kegiatan)
                ->addColumn('nilai', function ($row) {
                    if (in_array($row->activity_type, ['post-test', 'pre-test'])) {
                        try {
                            $total = 0;
                            foreach (unserialize($row->response) as $item) {
                                $total += $item['score'];
                            }

                            return $total;
                        } catch (\Exception) {
                            return 'Format Error';
                        }
                    }

                    return '-';
                })
                ->addColumn('updated_at', fn ($row) => $row->updated_at)
                ->make();
        }

        return Inertia::render('Admin/Result/Show', [
            'course' => ['id' => $course->id, 'title' => $course->title, 'slug' => $course->slug],
            'names' => User::role('participant')->select('id', 'name')->get(),
            'activities' => Activity::where('course_id', $course->id)->get(['id', 'title']),
            'filters' => $filters,
        ]);
    }

    public function postTest(Course $course)
    {
        $filters = request()->only(['nama', 'kegiatan']);
        $data = $this->resultService->getRawResults($course->id, $filters);
        $grouped = $this->resultService->groupAverageScores($data);

        if (request()->ajax() && ! request()->header('X-Inertia')) {
            return DataTables::of($grouped)
                ->addIndexColumn()
                ->addColumn('nip', fn ($row) => $row['nip'])
                ->addColumn('nama', fn ($row) => $row['name'])
                ->addColumn('jabatan', fn ($row) => $row['jabatan'])
                ->addColumn('rata_rata_nilai', fn ($row) => $row['avg_score'])
                ->addColumn('updated_at', fn ($row) => $row['updated_at'])
                ->make();
        }

        return Inertia::render('Admin/Result/PostTest', [
            'course' => ['id' => $course->id, 'title' => $course->title, 'slug' => $course->slug],
        ]);
    }
}
