<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ParticipantResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ParticipantController extends Controller
{
    /**
     * Daftar peserta (filter by status kepegawaian)
     */
    public function index(Request $request)
    {
        $query = User::role('Participant');

        // Filter by employee status
        if ($request->has('employee_status')) {
            $statusFilter = $request->employee_status;
            $query->whereHas('schedules', function ($q) use ($statusFilter) {
                $q->whereHas('course', function ($cq) use ($statusFilter) {
                    $cq->whereHas('employeeStatus', function ($eq) use ($statusFilter) {
                        $eq->where('title', $statusFilter);
                    });
                });
            });
        }

        // Search by name
        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $perPage = min($request->get('per_page', 15), 100);
        $participants = $query->latest()->paginate($perPage);

        return ParticipantResource::collection($participants)->additional([
            'success' => true,
            'message' => 'Data peserta berhasil diambil.',
        ]);
    }

    /**
     * Detail peserta + riwayat pelatihan
     */
    public function show(int $id)
    {
        $participant = User::role('Participant')->find($id);

        if (!$participant) {
            return response()->json([
                'success' => false,
                'message' => 'Peserta tidak ditemukan.',
            ], 404);
        }

        $learns = $participant->schedules()
            ->with('course.category')
            ->get()
            ->map(function ($schedule) use ($participant) {
                $course = $schedule->course;
                $learn = $course->learns()->where('participant_id', $participant->id)->first();

                return [
                'custom_unique_id' => Str::random(4),
                    'data_date' => now()->format('Y-m-d H:i:s'),
                    'course_id' => $course->id,
                    'course_title' => $course->title,
                    'category' => $course->category?->title,
                    'progress' => $learn?->progress,
                    'finished_at' => $learn?->finished_at?->format('Y-m-d H:i:s'),
                    'enrolled_at' => $learn?->created_at?->format('Y-m-d H:i:s'),
                ];
            });

        $nip = get_user_meta($participant->id, 'nip');

        return response()->json([
            'success' => true,
            'message' => 'Detail peserta berhasil diambil.',
            'data' => [
                'custom_unique_id' => Str::random(4),
                'data_date' => now()->format('Y-m-d H:i:s'),
                'id' => $participant->id,
                'name' => $participant->name,
                'nip' => $nip ? $this->maskNip($nip) : null,
                'total_courses' => $learns->count(),
                'total_completed' => $learns->whereNotNull('finished_at')->count(),
                'courses' => $learns,
            ],
        ]);
    }

    /**
     * Mask NIP for privacy
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
