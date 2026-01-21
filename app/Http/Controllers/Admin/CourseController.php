<?php

namespace App\Http\Controllers\Admin;

use App\Models\Course\Course;
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
                    $btn .= 'Detail</a>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['action', 'image'])
                ->addColumn('image', function ($row) {
                    $thumbnail = $row['thumbnail']
                        ? asset($row['thumbnail'])
                        : asset('images/no-image.png');

                    return '
                <div class="d-flex justify-content-center">
                    <img src="' . $thumbnail . '" alt="thumbnail" width="60" class="img-thumbnail">
                </div>
            ';
                })
                ->addColumn('category', function ($row) {
                    return $row['category'];
                })
                ->make();
        }

        return view('admin.course.index');
    }

    public function show(Course $course)
    {
        // Load schedules dari relasi
        $schedules = $course->schedules;

        $finishedParticipant = \DB::table('learns')
            ->join('users', 'users.id', '=', 'learns.participant_id')
            ->leftJoin('user_meta as job_title_meta', function ($join) {
                $join->on('job_title_meta.user_id', '=', 'users.id')
                    ->where('job_title_meta.key', '=', 'job_title');
            })
            ->where('learns.course_id', $course->id)
            ->whereNotNull('learns.finished_at')
            ->select(
                'users.name', // Ini nama user, tetap 'name' (kecuali jika kolom DB user juga berubah)
                'job_title_meta.value as job_title'
            )
            ->get();

        // Contoh pengisian ongoing jika diperlukan:
        $onGoingParticipant = \DB::table('learns')
            ->join('users', 'users.id', '=', 'learns.participant_id')
            ->where('learns.course_id', $course->id)
            ->whereNull('learns.finished_at')
            ->select('users.name')
            ->get();

        return view('admin.course.show', compact('course', 'schedules', 'finishedParticipant', 'onGoingParticipant'));
    }
}
