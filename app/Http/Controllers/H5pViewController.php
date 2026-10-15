<?php

namespace App\Http\Controllers;

use App\Models\Course\H5p;
use App\Models\Learn\Learn;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Menyajikan halaman terisolasi untuk memutar paket H5P, serta menyalurkan
 * file konten paket lewat route yang mendukung HTTP Range. Range penting
 * agar video (HTML5 <video> / H5P InteractiveVideo) bisa diputar & di-seek —
 * server statis PHP built-in (artisan serve) tidak mendukung Range.
 */
class H5pViewController
{
    /** MIME eksplisit agar tidak diblokir nosniff (mis. .js jangan text/plain). */
    const MIME = [
        'js'    => 'application/javascript',
        'mjs'   => 'application/javascript',
        'css'   => 'text/css',
        'json'  => 'application/json',
        'svg'   => 'image/svg+xml',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'gif'   => 'image/gif',
        'webp'  => 'image/webp',
        'mp4'   => 'video/mp4',
        'webm'  => 'video/webm',
        'ogg'   => 'video/ogg',
        'ogv'   => 'video/ogg',
        'mp3'   => 'audio/mpeg',
        'wav'   => 'audio/wav',
        'm4a'   => 'audio/mp4',
        'vtt'   => 'text/vtt',
        'woff'  => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf'   => 'font/ttf',
        'otf'   => 'font/otf',
        'eot'   => 'application/vnd.ms-fontobject',
        'html'  => 'text/html',
        'xml'   => 'application/xml',
        'txt'   => 'text/plain',
    ];

    public function show(Request $request, string $folder)
    {
        abort_unless($this->validFolder($folder), 404);

        $dir = public_path('assets/media/h5p/' . $folder);
        abort_unless(is_dir($dir) && is_file($dir . DIRECTORY_SEPARATOR . 'h5p.json'), 404);

        $h5p = H5p::with('activity')->where('folder', $folder)->first();
        $user = $request->user();
        $runtimeUrl = null;
        $h5pUser = null;

        $learn = $user && $h5p?->activity
            ? Learn::where('participant_id', $user->id)
                ->where('course_id', $h5p->activity->course_id)
                ->first()
            : null;

        if ($learn) {
            $runtimeUrl = route('h5p.runtime', ['folder' => $folder])
                . '?content_id=:contentId&data_type=:dataType&sub_content_id=:subContentId&_token='
                . urlencode(csrf_token());
            $h5pUser = [
                'id' => (int) $user->id,
                'name' => (string) $user->name,
                'mail' => (string) $user->email,
            ];
        }

        return view('h5p.view', [
            // Semua file paket dilayani lewat route Range-capable.
            'contentBase' => url('/h5p-content/' . $folder),
            'contentId' => $h5p ? 'activity-' . $h5p->activity_id : $folder,
            'runtimeUrl' => $runtimeUrl,
            'h5pUser' => $h5pUser,
        ]);
    }

    /**
     * Salurkan satu file di dalam paket H5P dengan dukungan Range.
     */
    public function content(Request $request, string $folder, string $path)
    {
        abort_unless($this->validFolder($folder), 404);

        // Cegah path traversal.
        $path = str_replace('\\', '/', $path);
        abort_if(str_contains($path, '..') || str_starts_with($path, '/'), 404);

        $base = public_path('assets/media/h5p/' . $folder);
        $full = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);

        // Pastikan file benar-benar di dalam folder paket.
        $realBase = realpath($base);
        $realFull = realpath($full);
        abort_if($realBase === false || $realFull === false, 404);
        abort_unless(str_starts_with($realFull, $realBase) && is_file($realFull), 404);

        $ext = strtolower(pathinfo($realFull, PATHINFO_EXTENSION));

        $response = new BinaryFileResponse($realFull);
        $response->headers->set('Content-Type', self::MIME[$ext] ?? 'application/octet-stream');
        $response->headers->set('Accept-Ranges', 'bytes');
        // Folder paket selalu unik saat unggahan diganti, sehingga aset aman
        // di-cache lama dan tidak membebani PHP pada kunjungan berikutnya.
        $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');
        // BinaryFileResponse memproses header Range saat prepare() (dipanggil kernel).

        return $response;
    }

    protected function validFolder(string $folder): bool
    {
        return (bool) preg_match('/^h5p_[0-9a-f]+$/', $folder);
    }
}
