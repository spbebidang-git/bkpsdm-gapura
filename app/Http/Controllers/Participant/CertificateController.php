<?php

namespace App\Http\Controllers\Participant;

use App\Models\Course\Course;
use App\Models\Learn\Learn;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CertificateController
{
    public function __invoke(Request $request)
    {

        // dd($request->all());
        $user = Auth::user();

        $course = Course::with('category', 'activities')->where('slug', $request->course)->first();

        $learn = Learn::where('course_id', $course->id)
            ->where('participant_id', $user->id)
            ->first();

        $sumValueInMinutes = $course->activities->sum('value');
        $sumInHour = $sumValueInMinutes / 60;

        $date = Carbon::parse($learn->finished_at)->locale('id');
        $date->settings(['formatFunction' => 'translatedFormat']);

        $data = [
            'no_urut' => 140,
            'nama_peserta' => $user->name . ' ' . get_user_meta($user->id, 'fc_glrblk'),
            'nip' => get_user_meta($user->id, 'nip'),
            'pangkat' => 'Penata Muda/III.a',
            'jabatan' => get_user_meta($user->id, 'nama_jabatan'),
            'departemen' => 'BKPSDM Kabupaten Gresik',
            'title' => $course->category->title,
            'sub_title' => $course->title,
            'date_finish' => $date->format('j F Y'),
            'sum_value' => round($sumInHour),
        ];
        $pdf = Pdf::loadView('certificate', $data)->setPaper('a4', 'landscape');

        // Format nama file dengan Nama dan Title
        $fileName = "Sertifikat Pelatihan | {$user->name} | {$course->title}.pdf";

        // Bersihkan karakter yang tidak valid di nama file (opsional)
        $fileName = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '-', $fileName);


        return response()->streamDownload(
            fn() => print($pdf->output()),
            $fileName,
            ["Content-Type" => "application/pdf"]
        );

        // return $pdf->download($fileName);
        // return $pdf->stream('certificate.pdf');
    }
}
