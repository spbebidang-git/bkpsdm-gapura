<?php

namespace App\Http\Controllers\Admin;

use App\Services\Course\Review\ReviewService;
use Yajra\DataTables\Facades\DataTables;

class ReviewController
{
    protected $review;

    public function __construct(ReviewService $reviewService)
    {
        $this->review = $reviewService;
    }

    public function index()
    {
        if (request()->ajax()) {
            return DataTables::of($this->review->getByAdmin())
                ->addIndexColumn()
                ->addColumn('course', function ($row) {
                    return $row['course'];
                })
                ->addColumn('user', function ($row) {
                    return $row['user'];
                })
                ->addColumn('star', function ($row) {
                    return str_repeat('⭐', $row['star']);
                })
                ->addColumn('description', function ($row) {
                    return \Str::limit($row['description'], 60);
                })
                ->addColumn('action', function ($row) {
                    $btn  = '<div class="d-flex justify-content-center gap-2">';
                    $btn .= '<form action="' . route('admin.review.destroy', $row['id']) . '" method="POST" onsubmit="confirmDelete(event)">';
                    $btn .= csrf_field() . method_field('DELETE');
                    $btn .= '<button type="submit" class="btn btn-sm btn-danger">';
                    $btn .= '<i class="bx bx-trash"></i></button>';
                    $btn .= '</form>';
                    $btn .= '</div>';
                    return $btn;
                })
                ->rawColumns(['action', 'star'])
                ->make();
        }

        return view('admin.review.index');
    }

    public function destroy($id)
    {
        $this->review->delete($id);
        return redirect()->back()->with('success', 'Review deleted successfully.');
    }
}
