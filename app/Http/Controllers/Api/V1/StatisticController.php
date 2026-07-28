<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Course\Course;
use App\Models\Course\Review;
use App\Models\EmployeeStatus;
use App\Models\Learn\Learn;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StatisticController extends Controller
{
    /**
     * Ringkasan keseluruhan platform
     */
    public function overview()
    {
        $totalCourses = Course::where('status', 'publish')->count();
        $totalParticipants = User::role('Participant')->count();
        $totalInstructors = User::role('Instructor')->count();
        $totalLearns = Learn::count();
        $totalCompleted = Learn::whereNotNull('finished_at')->count();
        $completionRate = $totalLearns > 0 ? round(($totalCompleted / $totalLearns) * 100, 2) : 0;

        $avgRating = Review::avg('star') ?? 0;

        return response()->json([
            'success' => true,
            'message' => 'Statistik keseluruhan berhasil diambil.',
            // dibungkus array biar konsisten sama API categories (meski data tunggal)
            'data' => [[
                'custom_unique_id' => Str::random(4),
                'data_date' => now()->format('Y-m-d H:i:s'),
                'total_courses' => $totalCourses,
                'total_participants' => $totalParticipants,
                'total_instructors' => $totalInstructors,
                'total_enrollments' => $totalLearns,
                'total_completed' => $totalCompleted,
                'completion_rate' => $completionRate . '%',
                'average_rating' => round($avgRating, 2),
            ]],
        ]);
    }

    /**
     * Statistik per tahun
     */
    public function yearly(Request $request)
    {
        $year = $request->get('year', now()->year);

        $coursesCount = Course::where('status', 'publish')
            ->whereYear('created_at', $year)
            ->count();

        $enrollments = Learn::whereYear('created_at', $year)->count();
        $completions = Learn::whereYear('finished_at', $year)->count();
        $completionRate = $enrollments > 0 ? round(($completions / $enrollments) * 100, 2) : 0;

        $newParticipants = User::role('Participant')
            ->whereYear('created_at', $year)
            ->count();

        // Monthly breakdown
        $monthly = Learn::selectRaw('MONTH(created_at) as month, COUNT(*) as enrollments')
            ->whereYear('created_at', $year)
            ->groupBy(DB::raw('MONTH(created_at)'))
            ->orderBy('month')
            ->get()
            ->map(function ($item) use ($year) {
                $completionsInMonth = Learn::whereYear('finished_at', $year)
                    ->whereMonth('finished_at', $item->month)
                    ->count();

                return [
                    'month' => $item->month,
                    'enrollments' => $item->enrollments,
                    'completions' => $completionsInMonth,
                ];
            });

        return response()->json([
            'success' => true,
            'message' => "Statistik tahun {$year} berhasil diambil.",
            'data' => [
                'custom_unique_id' => Str::random(4),
                'data_date' => now()->format('Y-m-d H:i:s'),
                'year' => (int) $year,
                'total_courses' => $coursesCount,
                'total_enrollments' => $enrollments,
                'total_completions' => $completions,
                'completion_rate' => $completionRate . '%',
                'new_participants' => $newParticipants,
                'monthly' => $monthly,
            ],
        ]);
    }

    /**
     * Distribusi peserta per status kepegawaian
     */
    public function employeeStatus()
    {
        $statuses = EmployeeStatus::withCount('courses')->get();

        $data = $statuses->map(function ($status) {
            // Count unique participants enrolled in courses with this employee status
            $participantCount = Learn::whereHas('course.employeeStatus', function ($query) use ($status) {
                $query->where('employee_status.id', $status->id);
            })->distinct('participant_id')->count('participant_id');

            return [
                'custom_unique_id' => Str::random(4),
                'data_date' => now()->format('Y-m-d H:i:s'),
                'status' => $status->title,
                'total_courses' => $status->courses_count,
                'total_participants' => $participantCount,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Distribusi per status kepegawaian berhasil diambil.',
            'data' => $data,
        ]);
    }
}
