<?php

namespace App\Repositories\Reporting;

use App\Models\Course\Course;
use App\Models\Course\Review;
use App\Models\Identity\EmployeeStatus;
use App\Models\Identity\User;
use App\Models\Learning\Learn;
use App\Repositories\Reporting\Contracts\PlatformStatisticRepositoryInterface;
use Illuminate\Support\Facades\DB;

class PlatformStatisticRepository implements PlatformStatisticRepositoryInterface
{
    public function overview(): array
    {
        return [
            'total_courses' => Course::where('status', 'publish')->count(),
            'total_participants' => User::role('participant')->count(),
            'total_instructors' => User::role('trainer')->count(),
            'total_enrollments' => Learn::count(),
            'total_completed' => Learn::whereNotNull('finished_at')->count(),
            'average_rating' => Review::avg('star') ?? 0,
        ];
    }

    public function yearly(int $year): array
    {
        $monthly = Learn::selectRaw('MONTH(created_at) as month, COUNT(*) as enrollments')
            ->whereYear('created_at', $year)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy('month')->get()->map(fn ($item) => [
                'month' => $item->month,
                'enrollments' => $item->enrollments,
                'completions' => Learn::whereYear('finished_at', $year)
                    ->whereMonth('finished_at', $item->month)->count(),
            ]);

        return [
            'total_courses' => Course::where('status', 'publish')->whereYear('created_at', $year)->count(),
            'total_enrollments' => Learn::whereYear('created_at', $year)->count(),
            'total_completions' => Learn::whereYear('finished_at', $year)->count(),
            'new_participants' => User::role('participant')->whereYear('created_at', $year)->count(),
            'monthly' => $monthly,
        ];
    }

    public function employeeStatus(): array
    {
        return EmployeeStatus::withCount('courses')->get()->map(function ($status) {
            return [
                'status' => $status->title,
                'total_courses' => $status->courses_count,
                'total_participants' => Learn::whereHas('course.employeeStatus', fn ($query) =>
                    $query->where('employee_status.id', $status->id))
                    ->distinct('participant_id')->count('participant_id'),
            ];
        })->all();
    }
}
