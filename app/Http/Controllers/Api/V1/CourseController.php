<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CourseResource;
use App\Models\Course\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    /**
     * Daftar semua pelatihan (dengan filter & pagination)
     */
    public function index(Request $request)
    {
        $query = Course::with(['category', 'employeeStatus'])
            ->where('status', 'publish');

        // Filter by category
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by type (schedule/unschedule) → access_mode
        if ($request->has('type')) {
            $mode = $request->type === 'schedule' ? 'scheduled'
                : ($request->type === 'unschedule' ? 'open' : $request->type);
            $query->where('access_mode', $mode);
        }

        // Filter by year
        if ($request->has('year')) {
            $query->whereYear('created_at', $request->year);
        }

        // Search by title
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $perPage = min($request->get('per_page', 15), 100);
        $courses = $query->latest()->paginate($perPage);

        return CourseResource::collection($courses)->additional([
            'success' => true,
            'message' => 'Data pelatihan berhasil diambil.',
        ]);
    }

    /**
     * Detail pelatihan per slug
     */
    public function show(string $slug)
    {
        $course = Course::with(['category', 'schedules', 'activities', 'employeeStatus'])
            ->where('slug', $slug)
            ->where('status', 'publish')
            ->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Pelatihan tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail pelatihan berhasil diambil.',
            'data' => new CourseResource($course),
        ]);
    }

    /**
     * Daftar peserta per pelatihan
     */
    public function participants(string $slug, Request $request)
    {
        $course = Course::with('schedules')->where('slug', $slug)->where('status', 'publish')->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Pelatihan tidak ditemukan.',
            ], 404);
        }

        $learns = $course->learns()
            ->with('participant')
            ->paginate($request->get('per_page', 15));

        $learns->getCollection()->transform(function ($learn) {
            $participant = $learn->participant;
            $nip = get_user_meta($participant->id, 'nip');

            return [
                'custom_unique_id' => Str::random(4),
                'data_date' => now()->format('Y-m-d H:i:s'),
                'id' => $participant->id,
                'name' => $participant->name,
                'nip' => $nip ? $this->maskNip($nip) : null,
                'progress' => $learn->progress,
                'finished_at' => $learn->finished_at?->format('Y-m-d H:i:s'),
                'enrolled_at' => $learn->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar peserta berhasil diambil.',
            'data' => $learns,
        ]);
    }

    /**
     * Statistik pelatihan
     */
    public function statistics(string $slug)
    {
        $course = Course::with(['schedules', 'reviews', 'learns'])
            ->where('slug', $slug)
            ->where('status', 'publish')
            ->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Pelatihan tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Statistik pelatihan berhasil diambil.',
            'data' => [
                'custom_unique_id' => Str::random(4),
                'data_date' => now()->format('Y-m-d H:i:s'),
                'course_title' => $course->title,
                'total_participant' => $course->total_participant,
                'total_passed' => $course->total_passed,
                'efektifitas' => $course->efektifitas . '%',
                'rating' => $course->getRating(),
                'type' => $course->type,
                'jam_pelajaran' => $course->jam_pelajaran,
            ],
        ]);
    }

    /**
     * Hasil pre-test & post-test per pelatihan
     */
    public function results(string $slug, Request $request)
    {
        $course = Course::where('slug', $slug)->where('status', 'publish')->first();

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Pelatihan tidak ditemukan.',
            ], 404);
        }

        $learns = $course->learns()
            ->with(['participant', 'learnItems'])
            ->whereNotNull('finished_at')
            ->paginate($request->get('per_page', 15));

        $learns->getCollection()->transform(function ($learn) {
            $participant = $learn->participant;
            $nip = get_user_meta($participant->id, 'nip');

            $preTest = $learn->learnItems->where('type', 'pre-test')->first();
            $postTest = $learn->learnItems->where('type', 'post-test')->first();

            $preTestData = null;
            if ($preTest && $preTest->response) {
                $responsePre = unserialize($preTest->response);
                $preTestData = [
                    'total_score' => getTotalScore($responsePre),
                    'correct' => getCorrectAnswer($responsePre),
                    'wrong' => getWrongAnswer($responsePre),
                ];
            }

            $postTestData = null;
            if ($postTest && $postTest->response) {
                $responsePost = unserialize($postTest->response);
                $postTestData = [
                    'total_score' => getTotalScore($responsePost),
                    'correct' => getCorrectAnswer($responsePost),
                    'wrong' => getWrongAnswer($responsePost),
                ];
            }

            return [
                'custom_unique_id' => Str::random(4),
                'data_date' => now()->format('Y-m-d H:i:s'),
                'participant' => [
                    'id' => $participant->id,
                    'name' => $participant->name,
                    'nip' => $nip ? $this->maskNip($nip) : null,
                ],
                'pre_test' => $preTestData,
                'post_test' => $postTestData,
                'finished_at' => $learn->finished_at?->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Hasil pelatihan berhasil diambil.',
            'data' => $learns,
        ]);
    }

    /**
     * Mask NIP for privacy: show first 4 and last 2 chars
     */
    private function maskNip(string $nip): string
    {
        $length = strlen($nip);
        if ($length <= 6) {
            return str_repeat('*', $length);
        }

        return substr($nip, 0, 4) . str_repeat('*', $length - 6) . substr($nip, -2);
    }
}
