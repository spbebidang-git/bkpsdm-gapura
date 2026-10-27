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

        return $this->createRecord($data, $folder);
    }

    /**
     * Buat H5P dari blob paket yang sudah diekstrak dari arsip Moodle.
     */
    public function createH5pFromPath(array $data): H5p
    {
        $folder = $this->extractPackage($data['package_path']);

        return $this->createRecord($data, $folder);
    }

    /**
     * Buat baris H5P tanpa paket. Dipakai ketika metadata aktivitas ada di
     * Moodle tetapi file .h5p tidak disertakan dalam backup.
     */
    public function createPlaceholder(array $data): H5p
    {
        return H5p::updateOrCreate(
            ['activity_id' => $data['activity_id']],
            [
                'sub_title'    => $data['sub_title'] ?? null,
                'folder'       => '',
                'main_library' => null,
                'duration'     => $data['duration'] ?? 0,
            ]
        );
    }

    protected function createRecord(array $data, string $folder): H5p
    {
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
        $ready = $this->folderIsReady($h5p?->folder);

        return [
            'activity_id' => $activity->id,
            'title'       => $activity->title,
            'type'        => 'h5p',
            'data'        => [
                'duration'     => $h5p->duration ?? 0,
                'h5p_path'     => $ready
                    ? '/' . trim(H5p::$directory, '/') . '/' . $h5p->folder
                    : null,
                'main_library' => $h5p->main_library ?? null,
                'ready'        => $ready,
            ],
        ];
    }

    public function getH5pForEdit($activity): array
    {
        $h5p = H5p::where('activity_id', $activity->id)->first();
        $ready = $this->folderIsReady($h5p?->folder);

        return [
            'id'           => $activity->id,
            'title'        => $activity->title,
            'value'        => $activity->value,
            'instruction'  => $activity->instruction,
            'sub_title'    => $h5p->sub_title ?? null,
            'duration'     => $h5p->duration ?? 0,
            'main_library' => $h5p->main_library ?? null,
            'folder'       => $h5p->folder ?? '',
            'h5p_path'     => $ready
                ? '/' . trim(H5p::$directory, '/') . '/' . $h5p->folder
                : null,
            'has_package'  => $ready,
        ];
    }

    public function hasPackage(int $activityId): bool
    {
        return $this->isReady(
            H5p::where('activity_id', $activityId)->first()
        );
    }

    public function isReady(?H5p $h5p): bool
    {
        return $this->folderIsReady($h5p?->folder);
    }

    public function updateH5p(array $data): bool
    {
        $h5p = H5p::where('activity_id', $data['activity_id'])->first();
        $oldFolder = null;

        if (!$h5p) {
            $h5p = $this->createPlaceholder([
                'activity_id' => $data['activity_id'],
                'sub_title'   => $data['sub_title'] ?? null,
                'duration'    => $data['duration'] ?? 0,
            ]);
        }

        $h5p->sub_title = $data['sub_title'] ?? $h5p->sub_title;
        $h5p->duration  = $data['duration'] ?? $h5p->duration;

        // Ganti paket hanya bila ada unggahan baru.
        if (!empty($data['package']) && $data['package'] instanceof UploadedFile) {
            $oldFolder = $h5p->folder;
            $folder = $this->extractPackage($data['package']);
            $meta = $this->readManifest($folder);
            $h5p->folder = $folder;
            $h5p->main_library = $meta['mainLibrary'] ?? null;
            ActivityRuntimeState::where('activity_id', $h5p->activity_id)->delete();
        } elseif (!$this->folderIsReady($h5p->folder)) {
            throw new RuntimeException('Paket H5P wajib diunggah untuk melengkapi aktivitas hasil impor Moodle.');
        }

        $h5p->save();

        // Paket lama baru dihapus setelah metadata paket baru berhasil disimpan.
        if ($oldFolder !== null && $oldFolder !== $h5p->folder) {
            $this->deleteFolder($oldFolder);
        }

        return true;
    }

    /**
     * Ekstrak file .h5p ke folder unik di public. Mengembalikan nama folder.
     */
    protected function extractPackage(UploadedFile|string $package): string
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

        $packagePath = $package instanceof UploadedFile ? $package->getRealPath() : $package;
        if (!$packagePath || !is_file($packagePath)) {
            $this->deleteFolder($folderName);
            throw new RuntimeException('File paket H5P tidak ditemukan.');
        }

        $zip = new ZipArchive();
        if ($zip->open($packagePath) !== true) {
            $this->deleteFolder($folderName);
            throw new RuntimeException('File .h5p tidak dapat dibuka (bukan arsip ZIP yang valid).');
        }

        // Validasi: wajib ada h5p.json & folder content.
        if ($zip->locateName('h5p.json') === false) {
            $zip->close();
            $this->deleteFolder($folderName);
            throw new RuntimeException('Paket .h5p tidak valid: h5p.json tidak ditemukan.');
        }

        // Tolak seluruh paket bila ada entri zip-slip. Sekadar melewati nama
        // berbahaya tidak cukup karena extractTo() mengekstrak semua entri.
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entry = $zip->getNameIndex($i);
            if ($entry === false) {
                continue;
            }
            $normalized = str_replace('\\', '/', $entry);
            if (
                str_starts_with($normalized, '/')
                || preg_match('#(^|/)\.\.(/|$)#', $normalized)
            ) {
                $zip->close();
                $this->deleteFolder($folderName);
                throw new RuntimeException('Paket .h5p tidak aman: terdapat path file yang tidak valid.');
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

        $this->enforceNoForwardSkipping($folderName);

        return $folderName;
    }

    /**
     * Samakan aturan navigasi H5P InteractiveVideo dengan pemutar video biasa:
     * peserta boleh mengulang mundur, tetapi tidak boleh melompat maju.
     *
     * Setelan ini milik paket (content.json -> override.preventSkippingMode),
     * bukan pemutar, dan paket dari Moodle tidak konsisten — sebagian 'none',
     * sebagian 'forward'. Jadi nilainya diseragamkan saat paket diekstrak.
     *
     * @return bool true bila berkas memang diubah.
     */
    public function enforceNoForwardSkipping(string $folderName): bool
    {
        $path = public_path(
            trim(H5p::$directory, '/') . '/' . $folderName . '/content/content.json'
        );

        if ($folderName === '' || !is_file($path)) {
            return false;
        }

        // Didekode sebagai objek, bukan array asosiatif: objek kosong di dalam
        // content.json harus tetap tertulis sebagai {} saat disimpan ulang.
        $content = json_decode((string) file_get_contents($path));

        // Hanya InteractiveVideo yang punya konsep navigasi timeline.
        if (!$content instanceof \stdClass || !isset($content->interactiveVideo)) {
            return false;
        }

        if (!isset($content->override) || !$content->override instanceof \stdClass) {
            $content->override = new \stdClass();
        }

        if (($content->override->preventSkippingMode ?? null) === 'forward') {
            return false;
        }

        $content->override->preventSkippingMode = 'forward';

        $encoded = json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            return false;
        }

        return file_put_contents($path, $encoded) !== false;
    }

    /**
     * Turunkan titik terjauh yang tercatat (maxTimeReached) ke posisi tonton
     * (progress) di dalam satu string state InteractiveVideo.
     *
     * preventSkippingMode=forward hanya melarang lompatan melampaui
     * maxTimeReached, dan nilai itu ikut tersimpan di state peserta. Selama
     * maxTimeReached boleh mendahului progress, peserta tetap bisa melompat
     * maju sampai titik itu pada kunjungan berikutnya. Nilainya tidak boleh
     * dibuang sama sekali: library tidak pernah memanggil video.seek() sendiri
     * saat memuat, jadi peserta memerlukan izin seek sampai progress agar bisa
     * melanjutkan dari posisi terakhir.
     *
     * @return array{state: string, from: float, to: float}|null
     *         null bila state tidak perlu (atau tidak bisa) diubah.
     */
    public function clampSkipHighWaterMark(string $state): ?array
    {
        $inner = json_decode($state, true);

        // Hanya InteractiveVideo yang menyimpan kedua kunci ini.
        if (!is_array($inner)
            || !is_numeric($inner['progress'] ?? null)
            || !is_numeric($inner['maxTimeReached'] ?? null)) {
            return null;
        }

        $from = (float) $inner['maxTimeReached'];
        $to = (float) $inner['progress'];

        if ($from <= $to) {
            return null;
        }

        $inner['maxTimeReached'] = $to;
        $encoded = json_encode($inner, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($encoded === false) {
            return null;
        }

        return ['state' => $encoded, 'from' => $from, 'to' => $to];
    }

    /**
     * Terapkan clampSkipHighWaterMark() pada seluruh state yang tersimpan di
     * satu baris runtime. State-nya tidak disimpan di sini; itu tugas pemanggil.
     *
     * @return array<int, array{sub: string, from: float, to: float}>
     *         Daftar perubahan; kosong bila tidak ada yang perlu diubah.
     */
    public function clampRuntimeSkipHighWaterMark(ActivityRuntimeState $runtime): array
    {
        $state = is_array($runtime->state) ? $runtime->state : [];
        $contentUserData = $state['content_user_data'] ?? null;

        if (!is_array($contentUserData)) {
            return [];
        }

        $changes = [];

        foreach ($contentUserData as $subContentId => $dataTypes) {
            if (!is_array($dataTypes) || !is_string($dataTypes['state'] ?? null)) {
                continue;
            }

            $clamped = $this->clampSkipHighWaterMark($dataTypes['state']);

            if ($clamped === null) {
                continue;
            }

            $contentUserData[$subContentId]['state'] = $clamped['state'];
            $changes[] = [
                'sub' => (string) $subContentId,
                'from' => $clamped['from'],
                'to' => $clamped['to'],
            ];
        }

        if ($changes === []) {
            return [];
        }

        $runtime->state = ['content_user_data' => $contentUserData];

        return $changes;
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
        if ($folderName === '' || !preg_match('/^h5p_[a-f0-9]{16}$/', $folderName)) {
            return;
        }

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

    protected function folderIsReady(?string $folderName): bool
    {
        if ($folderName === '' || $folderName === null) {
            return false;
        }

        return is_file(
            public_path(trim(H5p::$directory, '/') . '/' . $folderName . '/h5p.json')
        );
    }
}
