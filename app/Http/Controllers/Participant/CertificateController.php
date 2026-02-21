<?php

namespace App\Http\Controllers\Participant;

use App\Models\Course\Course;
use App\Models\Learn\Learn;
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
                return redirect()->back()->with('danger', 'Kursus tidak ditemukan.');
            }

            $learns = Learn::where('course_id', $course->id)
                ->where('participant_id', Auth::user()->id)
                ->first();

            if (!$learns) {
                return redirect()->back()->with('danger', 'Data pembelajaran tidak ditemukan. Pastikan kamu sudah mengikuti kursus ini.');
            }

            if (!$learns->url_certificate || $learns->url_certificate === '-') {
                return redirect()->back()->with('danger', 'Sertifikat dikirim ke SIMPEG, menunggu approval admin.');
            }

            return redirect()->back()->with('redirect_url', $learns->url_certificate);

        } catch (\Throwable $e) {
            Log::channel('app_errors')->error('Participant CertificateController error: ' . $e->getMessage(), [
                'user'   => Auth::id(),
                'course' => $request->course ?? null,
            ]);
            return redirect()->back()->with('danger', 'Terjadi kesalahan saat memuat sertifikat. Silakan coba lagi.');
        }
    }
}
