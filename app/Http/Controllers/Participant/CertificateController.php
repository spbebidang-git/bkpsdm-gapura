<?php

namespace App\Http\Controllers\Participant;

use App\Models\Course\Course;
use App\Models\Learn\Learn;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;

class CertificateController
{
    public function __invoke(Request $request)
    {
        $course = Course::where('slug', $request->course)->first();
        $learns = Learn::where('course_id', $course->id)->where('participant_id', Auth::user()->id)->first();

        if (!$learns->url_certificate || $learns->url_certificate == '-') {
            return redirect()->back()->with('danger', 'Sertifikat tidak ditemukan atau belum tersedia.');
        }
        return redirect()->back()->with('redirect_url', $learns->url_certificate);
    }
}
