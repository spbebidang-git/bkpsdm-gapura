<?php

namespace App\Http\Controllers\Admin;

use App\Services\Course\Review\ReviewService;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class ReviewController
{
    public function __construct(protected ReviewService $review) {}

    public function index()
    {
        if (request()->ajax() && ! request()->header('X-Inertia')) {
            return DataTables::of($this->review->getByAdmin())
                ->editColumn('description', fn ($row) => Str::limit($row['description'] ?? '', 80))
                ->only(['id', 'course', 'user', 'star', 'description', 'updated_at'])
                ->make();
        }

        return Inertia::render('Admin/Review/Index');
    }

    public function destroy($id)
    {
        $this->review->delete($id);

        return redirect()->back()->with('success', 'Review deleted successfully.');
    }
}
