<?php

namespace App\Http\Controllers;

use App\Models\Course\Scorm;
use App\Models\Learn\ActivityRuntimeState;
use App\Models\Learn\Learn;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Halaman terisolasi untuk menjalankan paket SCORM beserta SCORM API runtime
 * (lihat resources/views/scorm/view.blade.php), serta penyaluran file paket
 * dengan dukungan HTTP Range (penting untuk audio/video di dalam SCO).
 */
class ScormViewController
{
    public function show(Request $request, string $folder)
    {
        abort_unless($this->validFolder($folder), 404);

        $dir = public_path('assets/media/scorm/' . $folder);
        abort_unless(is_dir($dir) && is_file($dir . DIRECTORY_SEPARATOR . 'imsmanifest.xml'), 404);

        // Ambil launch_url dari DB (sumber tepercaya), fallback index.html.
        $scorm = Scorm::with('activity')->where('folder', $folder)->first();
        $launch = $scorm->launch_url ?? 'index.html';
        $version = $scorm->version ?? '1.2';
        $runtimeState = [];
        $runtimeUrl = null;

        if ($request->user() && $scorm?->activity) {
            $learn = Learn::where('participant_id', $request->user()->id)
                ->where('course_id', $scorm->activity->course_id)
                ->first();

            if ($learn) {
                $runtimeUrl = route('scorm.runtime.store', $folder);
                $runtime = ActivityRuntimeState::where('learn_id', $learn->id)
                    ->where('activity_id', $scorm->activity_id)
                    ->first();
                $runtimeState = data_get($runtime?->state, 'cmi', []);
            }
        }

        return view('scorm.view', [
            'contentBase' => url('/scorm-content/' . $folder),
            'launch'      => $launch,
            'version'     => $version,
            'runtimeState' => $runtimeState,
            'runtimeUrl' => $runtimeUrl,
            'learnerId' => $request->user()?->id,
            'learnerName' => $request->user()?->name,
        ]);
    }

    public function content(Request $request, string $folder, string $path)
    {
        abort_unless($this->validFolder($folder), 404);

        $path = str_replace('\\', '/', $path);
        abort_if(str_contains($path, '..') || str_starts_with($path, '/'), 404);

        $base = public_path('assets/media/scorm/' . $folder);
        $full = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

        $realBase = realpath($base);
        $realFull = realpath($full);
        abort_if($realBase === false || $realFull === false, 404);
        abort_unless(str_starts_with($realFull, $realBase) && is_file($realFull), 404);

        $ext = strtolower(pathinfo($realFull, PATHINFO_EXTENSION));

        $response = new BinaryFileResponse($realFull);
        $response->headers->set('Content-Type', H5pViewController::MIME[$ext] ?? 'application/octet-stream');
        $response->headers->set('Accept-Ranges', 'bytes');
        // Folder paket selalu unik saat unggahan diganti, sehingga aset aman
        // di-cache lama dan tidak membebani PHP pada kunjungan berikutnya.
        $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');

        return $response;
    }

    protected function validFolder(string $folder): bool
    {
        return (bool) preg_match('/^scorm_[0-9a-f]+$/', $folder);
    }
}
