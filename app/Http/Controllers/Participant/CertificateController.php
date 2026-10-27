<?php

namespace App\Http\Controllers\Participant;

use App\Models\Course\Course;
use App\Models\Learning\Learn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CertificateController
{
    public function __invoke(Request $request)
    {
        try {
            $course = Course::where('slug', $request->course)->first();

            if (!$course) {
                return redirect()->back()->with('danger', 'Kelas tidak ditemukan.');
            }

            $learns = Learn::where('course_id', $course->id)
                ->where('participant_id', Auth::user()->id)
                ->first();

            if (!$learns) {
                return redirect()->back()->with('danger', 'Data pembelajaran tidak ditemukan. Pastikan kamu sudah mengikuti kelas ini.');
            }

            if (!$learns->url_certificate || $learns->url_certificate === '-') {
                return redirect()->back()->with('danger', 'Sertifikat dikirim ke SIMPEG, menunggu approval admin.');
            }

            return redirect()->back()->with('redirect_url', $learns->url_certificate);

        } catch (\Throwable $e) {
            Log::error('Sertifikat pengguna gagal dimuat.', ['event' => 'participant.certificate.failed',
                'exception' => $e,
                'actor_id' => Auth::id(),
                'course' => $request->course ?? null,
            ]);
            return redirect()->back()->with('danger', 'Terjadi kesalahan saat memuat sertifikat. Silakan coba lagi.');
        }
    }
}
