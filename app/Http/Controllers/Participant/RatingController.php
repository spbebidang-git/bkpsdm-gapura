<?php

namespace App\Http\Controllers\Participant;

use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Learning\Learn;
use Illuminate\Http\Request;
use App\Models\Course\Course;
use App\Services\Identity\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use App\Services\Learning\LearnLifecycleService;
use Symfony\Component\HttpKernel\Exception\HttpException;

class RatingController
{
    protected $activity, $course, $participantId, $learn, $user;

    public function __construct(UserService $userService)
    {
        $this->participantId = Auth::user()->id;
        $this->user = $userService;
    }

    public function index(Course $course, $scheduleId)
    {
        return Inertia::render('Participant/Rating/Index', [
            'course' => $course,
            'progress' => Learn::where('participant_id', $this->participantId)->where('course_id', $course->id)->first()->progress ?? '0 %',
            'schedule_id' => $scheduleId,
            'initial_activity' =>  $course->activities->first() ? $course->activities->first()->id : 0,
        ]);
    }

    public function store(Request $request, Course $course, $scheduleId)
    {
        $request->validate([
            'rating' => 'required|numeric|min:1|max:5',
            'description' => 'nullable|string|max:1000'
        ]);

        try {
            DB::beginTransaction();

            $enrollment = \App\Models\Learning\Enrollment::query()->lockForUpdate()->find(request()->attributes->get('participant_enrollment')?->id);
            abort_unless($enrollment && app(\App\Services\Learning\ParticipantEnrollmentService::class)->canWrite($enrollment), 403, 'Masa akses kelas ini telah berakhir.');

            $course->reviews()->create([
                'star' => $request->rating,
                'description' => $request->input('description') ?? '',
                'user_id' => $this->participantId,
            ]);

            # Set Finished At
            $learn = Learn::where('participant_id', $this->participantId)
                ->where('course_id', $course->id)
                ->first();

            app(LearnLifecycleService::class)->finish($learn);

            // # POST SERTIFIKAT 
            // $token = Session::get('token');

            // $participantApi = Http::withHeaders([
            //     'apikey' => env('API_KEY'),
            //     'auth' => $token
            // ])->post(env('SSO_BASE_URL') . '/api/get_nip_data');
            // $response = Http::withHeaders([
            //     'apikey' => env('API_KEY'),
            //     'auth' => $token
            // ])->asForm()
            //     ->post(env('SSO_BASE_URL') . '/api/post_sertifikat', [
            //         'fc_kduker' => $participantApi->json()['fc_kduker'] ?? 0,
            //         'fc_kdsatker' => $participantApi->json()['fc_kdsatker'] ?? 0,
            //         'fc_kdsubsatker' => $participantApi->json()['fc_kdsubsatker'] ?? 0,
            //         'lama_diklat' => $course->jam_pelajaran,
            //         'jenis_waktu' => 'J',
            //         'kode_kegiatan' => $course->category->code,
            //         'nama_kegiatan' => $course->title,
            //         'tempat_kegiatan' => 'Dinas Komunikasi & Informatika',
            //         'penyelenggara' => 'BKPSDM Gresik',
            //         'tahun' => Carbon::now()->year,
            //         'tgl_mulai' => Carbon::parse($learn->created_at)->format('Y-m-d'),
            //         'tgl_selesai' => Carbon::parse($learn->finished_at)->format('Y-m-d'),
            //         'peserta' => get_user_meta($this->participantId, 'nip')
            //     ]);

            // if (!$response->successful()) {
            //     if (!$response->successful()) {
            //         DB::rollBack();
            //         return response()->json([
            //             'success' => false,
            //             'message' => 'Gagal mengirim data sertifikat. Silakan coba lagi nanti.'
            //         ], 500);
            //     }
            // }

            DB::commit();

            return to_route('participant.survey.index', [$course->slug, $scheduleId]);
        } catch (\Throwable $th) {
            DB::rollBack();
            if ($th instanceof HttpException) throw $th;
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.'
            ], 500);
        }
    }
}
