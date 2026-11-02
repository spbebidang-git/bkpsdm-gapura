<?php

namespace App\Services\System;

use App\Logging\LogReader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class AdminLogService
{
    public function indexData(Request $request, array $validated): array
    {
        $selected = $validated['file'] ?? 'laravel-'.now()->format('Y-m-d').'.log';
        $filters = array_replace(['level' => '', 'q' => '', 'request_id' => ''], array_filter(array_intersect_key($validated, array_flip(['level', 'q', 'request_id'])), fn ($value) => $value !== null));
        $reading = app(LogReader::class)->tail($this->safeFile(storage_path('logs'), $selected, '/^[a-zA-Z0-9_.-]+\\.log$/D'), $filters);
        $mayDownload = $this->mayDownloadBackup($request);
        $backups = [];
        foreach (['logs', 'daily', 'weekly', 'files'] as $kind) {
            $backups[$kind] = $mayDownload ? $this->listBackups($kind) : [];
        }

        return [
            'logFiles' => $this->listLogFiles(),
            'backups' => $backups,
            'backupStatus' => [
                'encryptionConfigured' => config('backup.recipients') !== [],
                'offsiteConfigured' => (bool) config('backup.remote_disk'),
            ],
            'selected' => $selected,
            'entries' => $reading['entries'],
            'reading' => array_diff_key($reading, ['entries' => true]),
            'filters' => $filters,
            'retention' => [
                'log_days' => (int) config('logging.channels.daily.days'),
                'log_backup_keep' => config('backup.log_keep'),
                'db_daily_keep' => config('backup.daily_keep'),
                'db_weekly_keep' => config('backup.weekly_keep'),
            ],
        ];
    }

    public function downloadFile(Request $request): array
    {
        $type = (string) $request->query('type', 'log');
        $name = (string) $request->query('file', '');
        if ($type === 'log') {
            $path = $this->safeFile(storage_path('logs'), $name, '/^[a-zA-Z0-9_.-]+\.log$/D');
            abort_unless($path, 404);
            $this->audit($request, 'log.download', $name);

            return ['path' => $path, 'name' => $name, 'type' => 'log'];
        }
        abort_unless($this->mayDownloadBackup($request), 403);
        abort_unless(in_array($type, ['logs', 'daily', 'weekly', 'files'], true), 404);
        $dir = config('backup.root').'/'.$type;
        $path = $this->safeFile($dir, $name, '/^[a-zA-Z0-9_-]+\.age$/D');
        abort_unless($path && $this->safeFile($dir, $name.'.json', '/^[a-zA-Z0-9_-]+\.age\.json$/D'), 404);
        try {
            $meta = json_decode(File::get($path.'.json'), true, flags: JSON_THROW_ON_ERROR);
            abort_unless(is_string($meta['sha256'] ?? null) && hash_equals($meta['sha256'], hash_file('sha256', $path)), 404);
        } catch (\JsonException) {
            abort(404);
        }
        $this->audit($request, 'backup.download', $name);

        return ['path' => $path, 'name' => $name, 'type' => 'backup'];
    }

    private function mayDownloadBackup(Request $request): bool
    {
        return $request->user()?->hasRole('super_admin') || ($request->user()?->can('backup.download') ?? false);
    }

    private function audit(Request $request, string $event, string $name): void
    {
        Log::channel('audit')->info('Berkas sistem diunduh.', ['event' => $event, 'actor_id' => $request->user()?->getAuthIdentifier(), 'file' => $name, 'request_id' => $request->attributes->get('request_id')]);
    }

    private function safeFile(string $dir, string $name, string $pattern): ?string
    {
        if ($name !== basename($name) || ! preg_match($pattern, $name) || is_link($dir)) {
            return null;
        }
        $path = $dir.'/'.$name;
        $root = realpath($dir);
        $real = realpath($path);

        return $root && $real && ! is_link($path) && is_file($real) && dirname($real) === $root ? $real : null;
    }

    private function listLogFiles(): array
    {
        $dir = storage_path('logs');
        if (! is_dir($dir)) {
            return [];
        }

        return collect(File::files($dir))->filter(fn ($f) => $this->safeFile($dir, $f->getFilename(), '/^[a-zA-Z0-9_.-]+\.log$/D'))
            ->sortByDesc(fn ($f) => $f->getMTime())->map(fn ($f) => ['name' => $f->getFilename(), 'size' => $f->getSize(), 'modified' => date('Y-m-d H:i:s', $f->getMTime())])->values()->all();
    }

    private function listBackups(string $kind): array
    {
        $dir = config('backup.root').'/'.$kind;
        if (! is_dir($dir)) {
            return [];
        }

        return collect(File::files($dir))->filter(fn ($f) => $this->safeFile($dir, $f->getFilename(), '/^[a-zA-Z0-9_-]+\.age$/D')
            && $this->safeFile($dir, $f->getFilename().'.json', '/^[a-zA-Z0-9_-]+\.age\.json$/D'))
            ->sortByDesc(fn ($f) => $f->getMTime())->take(30)->map(fn ($f) => ['name' => $f->getFilename(), 'size' => $f->getSize(), 'modified' => date('Y-m-d H:i:s', $f->getMTime()), 'type' => $kind])->values()->all();
    }
}
