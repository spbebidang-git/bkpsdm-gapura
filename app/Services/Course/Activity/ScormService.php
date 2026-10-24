<?php

namespace App\Services\Course\Activity;

use App\Models\Course\Scorm;
use App\Models\Learn\ActivityRuntimeState;
use App\Services\Learn\LearnService;
use Illuminate\Http\UploadedFile;
use RuntimeException;
use ZipArchive;

/**
 * Mengelola aktivitas SCORM: paket .zip (SCORM 1.2/2004) diunggah,
 * diekstrak ke folder publik, lalu dijalankan di sisi peserta dengan
 * SCORM API runtime sederhana (lihat resources/views/scorm/view.blade.php).
 */
class ScormService
{
    protected $learn;

    public function __construct(LearnService $learnService)
    {
        $this->learn = $learnService;
    }

    /** Tandai aktivitas SCORM selesai saat peserta lanjut. */
    public function storeScorm($participantId, $courseId, $activityId, $request)
    {
        $learnItem = [
            'type'     => 'scorm',
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

    public function createScorm(array $data): Scorm
    {
        return $this->createRecord($data, $this->extractPackage($data['package']));
    }

    /**
     * Buat aktivitas SCORM dari paket .zip yang sudah ada di disk (dipakai
     * impor .mbz, di mana paket diekstrak dari arsip Moodle lebih dulu).
     */
    public function createScormFromPath(array $data): Scorm
    {
        return $this->createRecord($data, $this->extractPackage($data['package_path']));
    }

    /**
     * Buat baris SCORM tanpa paket. Dipakai ketika metadata aktivitas ada di
     * Moodle tetapi file .zip tidak disertakan dalam backup.
     */
    public function createPlaceholder(array $data): Scorm
    {
        return Scorm::updateOrCreate(
            ['activity_id' => $data['activity_id']],
            [
                'sub_title'  => $data['sub_title'] ?? null,
                'folder'     => '',
                'launch_url' => '',
                'version'    => null,
                'duration'   => $data['duration'] ?? 0,
            ]
        );
    }

    protected function createRecord(array $data, string $folder): Scorm
    {
        $meta = $this->readManifest($folder);

        return Scorm::create([
            'activity_id' => $data['activity_id'],
            'sub_title'   => $data['sub_title'] ?? ($meta['title'] ?? null),
            'folder'      => $folder,
            'launch_url'  => $meta['launch'] ?? 'index.html',
            'version'     => $meta['version'] ?? null,
            'duration'    => $data['duration'] ?? 0,
        ]);
    }

    public function getScorm($activity): array
    {
        $scorm = Scorm::where('activity_id', $activity->id)->first();
        $ready = $this->folderIsReady($scorm?->folder);

        return [
            'activity_id' => $activity->id,
            'title'       => $activity->title,
            'type'        => 'scorm',
            'data'        => [
                'duration'   => $scorm->duration ?? 0,
                'scorm_path' => $ready
                    ? '/' . trim(Scorm::$directory, '/') . '/' . $scorm->folder
                    : null,
                'launch_url' => $scorm->launch_url ?? null,
                'version'    => $scorm->version ?? null,
                'ready'      => $ready,
            ],
        ];
    }

    /** Paket dianggap siap bila folder hasil ekstrak punya imsmanifest.xml. */
    protected function folderIsReady(?string $folderName): bool
    {
        if ($folderName === '' || $folderName === null) {
            return false;
        }

        return is_file(
            public_path(trim(Scorm::$directory, '/') . '/' . $folderName . '/imsmanifest.xml')
        );
    }

    public function getScormForEdit($activity): array
    {
        $scorm = Scorm::where('activity_id', $activity->id)->first();

        return [
            'id'          => $activity->id,
            'title'       => $activity->title,
            'value'       => $activity->value,
            'instruction' => $activity->instruction,
            'sub_title'   => $scorm->sub_title,
            'duration'    => $scorm->duration,
            'version'     => $scorm->version,
            'folder'      => $scorm->folder,
            'launch_url'  => $scorm->launch_url,
        ];
    }

    public function updateScorm(array $data): bool
    {
        $scorm = Scorm::where('activity_id', $data['activity_id'])->first();

        $scorm->sub_title = $data['sub_title'] ?? $scorm->sub_title;
        $scorm->duration  = $data['duration'] ?? $scorm->duration;

        if (!empty($data['package']) && $data['package'] instanceof UploadedFile) {
            $this->deleteFolder($scorm->folder);
            $folder = $this->extractPackage($data['package']);
            $meta = $this->readManifest($folder);
            $scorm->folder = $folder;
            $scorm->launch_url = $meta['launch'] ?? 'index.html';
            $scorm->version = $meta['version'] ?? null;
            ActivityRuntimeState::where('activity_id', $scorm->activity_id)->delete();
        }

        $scorm->save();

        return true;
    }

    /**
     * Ekstrak paket SCORM ke folder unik. Mengembalikan nama folder.
     */
    protected function extractPackage(UploadedFile|string $package): string
    {
        $folderName = 'scorm_' . bin2hex(random_bytes(8));
        $baseDir = public_path(trim(Scorm::$directory, '/'));
        $destDir = $baseDir . DIRECTORY_SEPARATOR . $folderName;

        if (!is_dir($baseDir) && !@mkdir($baseDir, 0775, true) && !is_dir($baseDir)) {
            throw new RuntimeException('Gagal menyiapkan direktori SCORM.');
        }
        if (!@mkdir($destDir, 0775, true) && !is_dir($destDir)) {
            throw new RuntimeException('Gagal membuat folder SCORM.');
        }

        $packagePath = $package instanceof UploadedFile ? $package->getRealPath() : $package;
        if (!$packagePath || !is_file($packagePath)) {
            $this->deleteFolder($folderName);
            throw new RuntimeException('File paket SCORM tidak ditemukan.');
        }

        $zip = new ZipArchive();
        if ($zip->open($packagePath) !== true) {
            $this->deleteFolder($folderName);
            throw new RuntimeException('File SCORM tidak dapat dibuka (bukan arsip ZIP yang valid).');
        }

        if ($zip->locateName('imsmanifest.xml') === false) {
            $zip->close();
            $this->deleteFolder($folderName);
            throw new RuntimeException('Paket SCORM tidak valid: imsmanifest.xml tidak ditemukan.');
        }

        if (!$zip->extractTo($destDir)) {
            $zip->close();
            $this->deleteFolder($folderName);
            throw new RuntimeException('Gagal mengekstrak paket SCORM.');
        }
        $zip->close();

        if (!is_file($destDir . DIRECTORY_SEPARATOR . 'imsmanifest.xml')) {
            $this->deleteFolder($folderName);
            throw new RuntimeException('Paket SCORM tidak valid: imsmanifest.xml tidak ada setelah ekstrak.');
        }

        return $folderName;
    }

    /**
     * Baca imsmanifest.xml untuk file peluncur (SCO), judul, & versi SCORM.
     */
    protected function readManifest(string $folderName): array
    {
        $path = public_path(trim(Scorm::$directory, '/') . '/' . $folderName . '/imsmanifest.xml');
        if (!is_file($path)) {
            return [];
        }

        $xml = @simplexml_load_file($path);
        if ($xml === false) {
            return [];
        }

        // Versi SCORM.
        $version = null;
        $ns = $xml->getNamespaces(true);
        foreach (['adlcp', 'imsss'] as $prefix) {
            // schemaversion ada di <metadata><schema>/<schemaversion>
        }
        $meta = $xml->metadata ?? null;
        if ($meta && isset($meta->schemaversion)) {
            $version = trim((string) $meta->schemaversion);
        }

        // File peluncur: resource SCO (adlcp:scormtype="sco") atau item identifierref.
        $launch = null;
        if (isset($xml->resources->resource)) {
            foreach ($xml->resources->resource as $res) {
                $attrs = $res->attributes();
                $adl = $res->attributes('adlcp', true);
                $scormType = $adl && isset($adl['scormtype']) ? (string) $adl['scormtype'] : '';
                $href = isset($attrs['href']) ? (string) $attrs['href'] : '';
                if ($href !== '' && (strtolower($scormType) === 'sco' || $launch === null)) {
                    $launch = $href;
                    if (strtolower($scormType) === 'sco') {
                        break;
                    }
                }
            }
        }

        // Judul.
        $title = null;
        if (isset($xml->organizations->organization->title)) {
            $title = trim((string) $xml->organizations->organization->title);
        }

        return [
            'launch'  => $launch,
            'version' => $version,
            'title'   => $title,
        ];
    }

    protected function deleteFolder(string $folderName): void
    {
        $dir = public_path(trim(Scorm::$directory, '/') . '/' . $folderName);
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
