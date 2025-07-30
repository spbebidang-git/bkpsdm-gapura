<?php

namespace App\Http\Controllers\Admin;

use App\Models\Course\Activity;
use Illuminate\Http\Request;
use App\Models\Course\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;
use App\Services\ResultService;


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

        if (request()->ajax()) {
            return DataTables::of($courses)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = '<div class="d-flex justify-content-center gap-2">';

                    // Tombol Detail
                    $btn .= '<a class="btn btn-sm btn-warning" href="' . route('admin.result.show', $row['slug']) . '">';
                    $btn .= 'Detail</a>';

                    // Tombol Post Test
                    $btn .= '<a class="btn btn-sm btn-primary" href="' . route('admin.result.post-test', $row['slug']) . '">';
                    $btn .= 'Post Test</a>';

                    $btn .= '</div>';
                    return $btn;
                })

                ->rawColumns(['action'])
                ->make();
        }

        return view('admin.result.index', compact('courses'));
    }

    public function show(Course $course)
    {
        $filters = request()->only(['nama', 'kegiatan']);
        $data = $this->resultService->getRawResults($course->id, $filters);

        if (request()->ajax()) {
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('nip', fn($row) => $row->nip)
                ->addColumn('nama', fn($row) => $row->name)
                ->addColumn('jabatan', fn($row) => $row->jabatan)
                ->addColumn('kegiatan', fn($row) => $row->kegiatan)
                ->addColumn('nilai', function ($row) {
                    if (in_array($row->activity_type, ['post-test', 'pre-test'])) {
                        try {
                            $total = 0;
                            foreach (unserialize($row->response) as $item) {
                                $total += $item['score'];
                            }
                            return $total;
                        } catch (\Exception $e) {
                            return 'Format Error';
                        }
                    }
                    return '-';
                })
                ->make();
        }

        $names = User::role('Participant')->select('id', 'name')->get();
        $activities = Activity::where('course_id', $course->id)->get();

        return view('admin.result.show', compact('names', 'activities'));
    }


    public function postTest(Course $course)
    {
        $filters = request()->only(['nama', 'kegiatan']);
        $data = $this->resultService->getRawResults($course->id, $filters);
        $grouped = $this->resultService->groupAverageScores($data);

        if (request()->ajax()) {
            return DataTables::of($grouped)
                ->addIndexColumn()
                ->addColumn('nip', fn($row) => $row['nip'])
                ->addColumn('nama', fn($row) => $row['name'])
                ->addColumn('jabatan', fn($row) => $row['jabatan'])
                ->addColumn('rata_rata_nilai', fn($row) => $row['avg_score'])
                ->make();
        }

        return view('admin.result.post-test');
    }
}
