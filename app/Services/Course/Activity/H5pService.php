<?php

namespace App\Services\Course\Activity;

use App\Models\Course\H5p;
use App\Models\Learn\ActivityRuntimeState;
use App\Services\Learn\LearnService;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use ZipArchive;

/**
 * Mengelola aktivitas H5P: paket .h5p (sebuah ZIP) diunggah terpisah,
 * diekstrak ke folder publik, lalu diputar di sisi peserta memakai
 * pustaka h5p-standalone (dari /vendor/h5p-standalone).
 */
class H5pService
{
    protected $learn;

    public function __construct(LearnService $learnService)
    {
        $this->learn = $learnService;
    }

    /**
     * Tandai aktivitas H5P selesai (seperti literature) saat peserta lanjut.
     */
    public function storeH5p($participantId, $courseId, $activityId, $request)
    {
        $learnItem = [
            'type'     => 'h5p',
            'status'   => 'done',
            'response' => null,
        ];

        $this->learn->createLearn($participantId, $courseId, $activityId, null, $learnItem);

        ActivityRuntimeState::where('activity_id', $activityId)
            ->whereHas('learn', function ($query) use ($participantId, $courseId) {
                $query->where('participant_id', $participantId)
                    ->where('course_id', $courseId);
            })
            ->update(['progress_status' => 'completed']);

        return true;
    }

    /**
     * Ekstrak paket .h5p & buat baris H5p untuk sebuah aktivitas.
     */
    public function createH5p(array $data): H5p
    {
        $folder = $this->extractPackage($data['package']);
        $meta = $this->readManifest($folder);

        return H5p::create([
            'activity_id'  => $data['activity_id'],
            'sub_title'    => $data['sub_title'] ?? ($meta['title'] ?? null),
            'folder'       => $folder,
            'main_library' => $meta['mainLibrary'] ?? null,
            'duration'     => $data['duration'] ?? 0,
        ]);
    }

    /**
     * Data untuk memutar H5P di sisi peserta/instruktur.
     */
    public function getH5p($activity): array
    {
        $h5p = H5p::where('activity_id', $activity->id)->first();

        return [
            'activity_id' => $activity->id,
            'title'       => $activity->title,
            'type'        => 'h5p',
            'data'        => [
                'duration'     => $h5p->duration ?? 0,
                'h5p_path'     => '/' . trim(H5p::$directory, '/') . '/' . $h5p->folder,
                'main_library' => $h5p->main_library,
            ],
        ];
    }

    public function getH5pForEdit($activity): array
    {
        $h5p = H5p::where('activity_id', $activity->id)->first();

        return [
            'id'           => $activity->id,
            'title'        => $activity->title,
            'value'        => $activity->value,
            'instruction'  => $activity->instruction,
            'sub_title'    => $h5p->sub_title,
            'duration'     => $h5p->duration,
            'main_library' => $h5p->main_library,
            'folder'       => $h5p->folder,
            'h5p_path'     => '/' . trim(H5p::$directory, '/') . '/' . $h5p->folder,
        ];
    }

    public function updateH5p(array $data): bool
    {
        $h5p = H5p::where('activity_id', $data['activity_id'])->first();

        $h5p->sub_title = $data['sub_title'] ?? $h5p->sub_title;
        $h5p->duration  = $data['duration'] ?? $h5p->duration;

        // Ganti paket hanya bila ada unggahan baru.
        if (!empty($data['package']) && $data['package'] instanceof UploadedFile) {
            $this->deleteFolder($h5p->folder);
            $folder = $this->extractPackage($data['package']);
            $meta = $this->readManifest($folder);
            $h5p->folder = $folder;
            $h5p->main_library = $meta['mainLibrary'] ?? null;
            ActivityRuntimeState::where('activity_id', $h5p->activity_id)->delete();
        }

        $h5p->save();

        return true;
    }

    /**
     * Ekstrak file .h5p ke folder unik di public. Mengembalikan nama folder.
     */
    protected function extractPackage(UploadedFile $package): string
    {
        $folderName = 'h5p_' . bin2hex(random_bytes(8));
        $baseDir = public_path(trim(H5p::$directory, '/'));
        $destDir = $baseDir . DIRECTORY_SEPARATOR . $folderName;

        if (!is_dir($baseDir) && !@mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
            throw new RuntimeException('Gagal menyiapkan direktori H5P.');
        }
        if (!@mkdir($destDir, 0775, true) && !is_dir($destDir)) {
            throw new RuntimeException('Gagal membuat folder H5P.');
        }

        $zip = new ZipArchive();
        if ($zip->open($package->getRealPath()) !== true) {
            $this->deleteFolder($folderName);
            throw new RuntimeException('File .h5p tidak dapat dibuka (bukan arsip ZIP yang valid).');
        }

        // Validasi: wajib ada h5p.json & folder content.
        if ($zip->locateName('h5p.json') === false) {
            $zip->close();
            $this->deleteFolder($folderName);
            throw new RuntimeException('Paket .h5p tidak valid: h5p.json tidak ditemukan.');
        }

        // Ekstraksi aman dari zip-slip.
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if ($entry === false) {
                continue;
            }
            $normalized = str_replace('\\', '/', $entry);
            if (str_contains($normalized, '../') || str_starts_with($normalized, '/')) {
                continue; // lewati entri berbahaya
            }
        }

        if (!$zip->extractTo($destDir)) {
            $zip->close();
            $this->deleteFolder($folderName);
            throw new RuntimeException('Gagal mengekstrak paket .h5p.');
        }
        $zip->close();

        if (!is_file($destDir . DIRECTORY_SEPARATOR . 'h5p.json')) {
            $this->deleteFolder($folderName);
            throw new RuntimeException('Paket .h5p tidak valid: h5p.json tidak ditemukan setelah ekstrak.');
        }

        return $folderName;
    }

    /**
     * Baca h5p.json untuk judul & pustaka utama.
     */
    protected function readManifest(string $folderName): array
    {
        $path = public_path(trim(H5p::$directory, '/') . '/' . $folderName . '/h5p.json');
        if (!is_file($path)) {
            return [];
        }
        $json = json_decode(file_get_contents($path), true);
        if (!is_array($json)) {
            return [];
        }
        return [
            'title'       => $json['title'] ?? null,
            'mainLibrary' => $json['mainLibrary'] ?? null,
        ];
    }

    protected function deleteFolder(string $folderName): void
    {
        $dir = public_path(trim(H5p::$directory, '/') . '/' . $folderName);
        if (!is_dir($dir)) {
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
        }
        @rmdir($dir);
    }
}
