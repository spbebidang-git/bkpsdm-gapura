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

class RatingController
{
    protected $activity, $course, $participantId, $learn, $user;

    public function __construct(UserService $userService)
    {
        $this->participantId = Auth::user()->id;
        $this->user = $userService;
    }

    public function index(Course $course)
    {
        return Inertia::render('Participant/Rating/Index', [
            'course' => $course,
            'progress' => Learn::where('participant_id', $this->participantId)->where('course_id', $course->id)->first()->progress ?? '0 %',
            'initial_activity' =>  $course->activities->first() ? $course->activities->first()->id : 0,
        ]);
    }

    public function store(Request $request, Course $course)
    {
        $request->validate([
            'rating' => 'required|numeric|min:1|max:5',
            'description' => 'required|string'
        ]);

        try {
            DB::beginTransaction();

            $course->reviews()->create([
                'star' => $request->rating,
                'description' => $request->description,
                'user_id' => $this->participantId,
            ]);

            # Set Finished At
            $learn = Learn::where('participant_id', $this->participantId)
                ->where('course_id', $course->id)
                ->first();

            $learn->finished_at = now();
            $learn->save();

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

            return to_route('participant.survey.index', [$course->slug, 1]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong.'
            ], 500);
        }
    }
}
