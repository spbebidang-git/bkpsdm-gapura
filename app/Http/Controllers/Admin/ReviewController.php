<?php

namespace App\Http\Controllers\Admin;

use App\Services\Course\Review\ReviewService;
use App\Models\Course\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Yajra\DataTables\Facades\DataTables;

class ReviewController
{
    public function __construct(protected ReviewService $review) {}

    public function index(Request $request)
    {
        $courseId = $request->integer('course_id') ?: null;
        $star = $request->integer('star') ?: null;

        if (request()->ajax() && ! request()->header('X-Inertia')) {
            return DataTables::of($this->review->getByAdmin($courseId, $star))
                ->editColumn('description', fn ($row) => Str::limit($row['description'] ?? '', 80))
                ->only(['id', 'course', 'user', 'star', 'description', 'updated_at'])
                ->make();
        }

        return Inertia::render('Admin/Review/Index', [
            'courses' => Course::query()->select('id', 'title')->orderBy('title')->get(),
            'filters' => ['course_id' => $courseId ? (string) $courseId : '', 'star' => $star ? (string) $star : ''],
        ]);
    }

    public function destroy($id)
    {
        $this->review->delete($id);

        return redirect()->back()->with('success', 'Review deleted successfully.');
    }
}
