<?php

namespace App\Http\Controllers\Admin\Management;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LogController
{
    private const TAIL_LINES = 200;

    private const MAX_READ_BYTES = 512_000; // 500 KB cap

    public function index(Request $request)
    {
        $selected = $request->query('file', 'laravel-'.now()->format('Y-m-d').'.log');
        $selected = basename((string) $selected);

        return Inertia::render('Admin/Management/Log/Index', [
            'logFiles' => $this->listLogFiles(),
            'backups' => [
                'logs' => $this->listBackups(storage_path('app/backups/logs'), 'logs'),
                'daily' => $this->listBackups(storage_path('app/backups/daily'), 'daily'),
                'weekly' => $this->listBackups(storage_path('app/backups/weekly'), 'weekly'),
            ],
            'selected' => $selected,
            'content' => $this->tailLog($selected),
            'retention' => [
                'log_days' => (int) env('LOG_DAILY_DAYS', 14),
                'log_backup_keep' => (int) env('LOG_BACKUP_KEEP', 30),
                'db_daily_keep' => 7,
                'db_weekly_keep' => 4,
            ],
        ]);
    }

    public function download(Request $request): BinaryFileResponse
    {
        $type = $request->query('type', 'log'); // log|logs|daily|weekly
        $name = basename((string) $request->query('file', ''));

        abort_if($name === '' || str_contains($name, '..'), 404);

        $path = match ($type) {
            'log' => storage_path('logs/'.$name),
            'logs' => storage_path('app/backups/logs/'.$name),
            'daily' => storage_path('app/backups/daily/'.$name),
            'weekly' => storage_path('app/backups/weekly/'.$name),
            default => null,
        };

        abort_if(! $path || ! File::isFile($path), 404);

        return response()->download($path, $name);
    }

    /**
     * @return list<array{name: string, size: int, modified: string}>
     */
    protected function listLogFiles(): array
    {
        $dir = storage_path('logs');
        if (! File::isDirectory($dir)) {
            return [];
        }

        return collect(File::files($dir))
            ->filter(fn ($f) => str_ends_with(strtolower($f->getFilename()), '.log'))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->map(fn ($f) => [
                'name' => $f->getFilename(),
                'size' => $f->getSize(),
                'modified' => date('Y-m-d H:i:s', $f->getMTime()),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{name: string, size: int, modified: string, type: string}>
     */
    protected function listBackups(string $dir, string $type): array
    {
        if (! File::isDirectory($dir)) {
            return [];
        }

        return collect(File::files($dir))
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->take(20)
            ->map(fn ($f) => [
                'name' => $f->getFilename(),
                'size' => $f->getSize(),
                'modified' => date('Y-m-d H:i:s', $f->getMTime()),
                'type' => $type,
            ])
            ->values()
            ->all();
    }

    protected function tailLog(string $filename): ?string
    {
        $path = storage_path('logs/'.$filename);
        if (! File::isFile($path) || ! str_ends_with(strtolower($filename), '.log')) {
            return null;
        }

        $size = File::size($path);
        $start = max(0, $size - self::MAX_READ_BYTES);
        $fh = fopen($path, 'rb');
        if ($fh === false) {
            return null;
        }
        if ($start > 0) {
            fseek($fh, $start);
            fgets($fh); // drop partial first line
        }
        $raw = stream_get_contents($fh) ?: '';
        fclose($fh);

        $lines = preg_split("/\r\n|\n|\r/", $raw) ?: [];
        $tail = array_slice($lines, -self::TAIL_LINES);
        $text = implode("\n", $tail);

        // Redact secrets if ever leaked into logs
        $text = preg_replace('/Bearer\s+\S+/', 'Bearer [REDACTED]', $text) ?? $text;
        $text = preg_replace(
            '/(access_token|refresh_token|password|client_secret)(["\s:=]+)[^\s",}]+/i',
            '$1$2[REDACTED]',
            $text
        ) ?? $text;

        return $text;
    }
}
