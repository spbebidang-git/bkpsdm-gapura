<?php

namespace App\Http\Controllers\Admin;

use App\Models\Course\Activity;
use Illuminate\Http\Request;
use App\Models\Course\Course;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class ResultController
{
    public function index()
    {
        $courses = Course::all();

        if (request()->ajax()) {
            return DataTables::of($courses)
                ->addIndexColumn()
                ->addColumn('action', function ($row) {
                    $btn = '<div class="d-flex justify-content-center gap-2">';
                    $btn .= '<a class="btn btn-sm btn-warning" href=' . route('admin.result.show', $row['slug']) . '>';
                    $btn .= 'Detail</a>';
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
        $data = DB::table('learn_items')
            ->join('learns', 'learn_items.learn_id', '=', 'learns.id')
            ->join('users', 'learns.participant_id', '=', 'users.id')
            ->leftJoin('user_meta as nip_meta', function ($join) {
                $join->on('nip_meta.user_id', '=', 'users.id')
                    ->where('nip_meta.key', '=', 'nip');
            })
            ->leftJoin('user_meta as jabatan_meta', function ($join) {
                $join->on('jabatan_meta.user_id', '=', 'users.id')
                    ->where('jabatan_meta.key', '=', 'jabatan');
            })
            ->join('activities', function ($join) {
                $join->on('activities.course_id', '=', 'learns.course_id')
                    ->on('activities.id', '=', 'learn_items.activity_id');
            })
            ->join('courses', 'courses.id', '=', 'learns.course_id')
            ->where('learns.course_id', $course->id)
            ->whereNotNull('learns.finished_at');

        // Filter: nama
        if ($nama = request()->get('nama')) {
            $data->where('users.id', $nama);
        }

        // Filter: kegiatan
        if ($kegiatan = request()->get('kegiatan')) {
            $data->where('activities.id', $kegiatan);
        }

        $data = $data->orderBy('activities.id')
            ->orderBy('users.name')
            ->select([
                DB::raw("COALESCE(nip_meta.value, '-') as nip"),
                'users.name',
                DB::raw("COALESCE(jabatan_meta.value, '-') as jabatan"),
                'courses.title as materi',
                'activities.title as kegiatan',
                'activities.type as activity_type',
                'learn_items.response as response',
            ])
            ->get();

        if (request()->ajax()) {
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('nip', function ($row) {
                    return $row->nip;
                })
                ->addColumn('nama', function ($row) {
                    return $row->name; // aslinya 'name' dari users.name
                })
                ->addColumn('jabatan', function ($row) {
                    return $row->jabatan;
                })
                ->addColumn('kegiatan', function ($row) {
                    return $row->kegiatan; // dari activities.title
                })
                ->addColumn('nilai', function ($row) {
                    if ($row->activity_type === 'post-test' || $row->activity_type === 'pre-test') {
                        Log::info('Serialized Response:', ['response' => $row->response]);

                        try {
                            $total = 0;
                            foreach (unserialize($row->response) as $item) {
                                $total += $item['score'];
                            }
                            return $total;
                        } catch (\Exception $e) {
                            Log::error('Gagal unserialize:', [
                                'error' => $e->getMessage(),
                                'response' => $row->response,
                            ]);
                            return 'Format Error';
                        }
                    }

                    return '-';
                })
                ->make();
        }

        $names = User::role('Participant')
            ->select('id', 'name')
            ->get();

        $activities = Activity::where('course_id', $course->id)->get();
        return view('admin.result.show', compact('names', 'activities'));
    }
}
