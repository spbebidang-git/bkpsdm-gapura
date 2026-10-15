<?php

namespace App\Http\Controllers\Admin\Management;

use App\Logging\Redactor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class LogController
{
    private const MAX_READ_BYTES = 512_000;

    public function index(Request $request)
    {
        $selected = (string) $request->query('file', 'laravel-'.now()->format('Y-m-d').'.log');
        $mayDownload = $this->mayDownloadBackup($request);
        $backups = [];
        foreach (['logs', 'daily', 'weekly', 'files'] as $kind) {
            $backups[$kind] = $mayDownload ? $this->listBackups($kind) : [];
        }

        return Inertia::render('Admin/Management/Log/Index', [
            'logFiles' => $this->listLogFiles(),
            'backups' => $backups,
            'backupStatus' => [
                'encryptionConfigured' => config('backup.recipients') !== [],
                'offsiteConfigured' => (bool) config('backup.remote_disk'),
            ],
            'selected' => $selected,
            'content' => $this->tailLog($selected),
            'retention' => [
                'log_days' => (int) config('logging.channels.daily.days'),
                'log_backup_keep' => config('backup.log_keep'),
                'db_daily_keep' => config('backup.daily_keep'),
                'db_weekly_keep' => config('backup.weekly_keep'),
            ],
        ])->toResponse($request)->header('Cache-Control', 'private, no-store');
    }

    public function download(Request $request): Response
    {
        $type = (string) $request->query('type', 'log');
        $name = (string) $request->query('file', '');
        if ($type === 'log') {
            $path = $this->safeFile(storage_path('logs'), $name, '/^[a-zA-Z0-9_.-]+\.log$/D');
            abort_unless($path, 404);
            $this->audit($request, 'log.download', $name);

            return response()->streamDownload(function () use ($path) {
                $fh = fopen($path, 'rb');
                if ($fh === false) {
                    return;
                }
                try {
                    $discard = false;
                    while (($line = fgets($fh, 65537)) !== false) {
                        $complete = str_ends_with($line, "\n") || feof($fh);
                        if (! $complete || $discard) {
                            if (! $discard) {
                                echo "[Oversized log record omitted]\n";
                            }
                            $discard = ! $complete;

                            continue;
                        }
                        echo app(Redactor::class)->text(rtrim($line, "\r\n"))."\n";
                    }
                } finally {
                    fclose($fh);
                }
            }, $name, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff', 'Content-Type' => 'text/plain; charset=UTF-8']);
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

        return response()->download($path, $name, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff', 'Content-Type' => 'application/octet-stream'])->setPrivate();
    }

    private function mayDownloadBackup(Request $request): bool
    {
        return $request->user()?->hasRole('super_admin') || ($request->user()?->can('backup.download') ?? false);
    }

    private function audit(Request $request, string $event, string $name): void
    {
        Log::channel('audit')->info($event, ['actor_id' => $request->user()?->getAuthIdentifier(), 'file' => $name, 'request_id' => $request->attributes->get('request_id')]);
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

    private function tailLog(string $filename): ?string
    {
        $path = $this->safeFile(storage_path('logs'), $filename, '/^[a-zA-Z0-9_.-]+\.log$/D');
        if (! $path) {
            return null;
        }
        $fh = fopen($path, 'rb');
        if ($fh === false) {
            return null;
        }
        try {
            $start = max(0, filesize($path) - self::MAX_READ_BYTES);
            fseek($fh, $start);
            if ($start > 0) {
                fgets($fh);
            }
            $raw = stream_get_contents($fh, self::MAX_READ_BYTES) ?: '';
        } finally {
            fclose($fh);
        }
        $lines = array_slice(preg_split('/\r\n|\n|\r/', $raw) ?: [], -200);

        return implode("\n", array_map(fn ($line) => app(Redactor::class)->text($line), $lines));
    }
}
