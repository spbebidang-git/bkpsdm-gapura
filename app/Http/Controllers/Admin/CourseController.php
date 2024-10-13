<?php

namespace App\Http\Controllers\Admin;

use App\Services\Course\CourseService;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CourseController
{
    protected $course;

    public function __construct(CourseService $courseService)
    {
        $this->course = $courseService;
    }

    public function index()
    {
        if (request()->ajax()) {
            return DataTables::of($this->course->getByAdmin())
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = '<div class="d-flex justify-content-center gap-2">';
                    $btn .= '<a class="btn btn-sm btn-warning" href=' . route('admin.course.show', $row['id']) . '>';
                    $btn .= '<i class="bx bx-eye"></i></a>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['action'])
                ->addColumn('image', function ($row) {
                    return $row['thumbnail'];
                })
                ->addColumn('category', function ($row) {
                    return $row['category'];
                })
                ->make();
        }

        return view('admin.course.index');
    }
}
