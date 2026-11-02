<?php

namespace App\Http\Controllers\Admin\Management;

use App\Http\Requests\Admin\LogIndexRequest;
use App\Logging\LogReader;
use App\Services\System\AdminLogService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class LogController
{
    public function __construct(private readonly AdminLogService $logs, private readonly LogReader $reader) {}

    public function index(LogIndexRequest $request)
    {
        return Inertia::render('Admin/Management/Log/Index', $this->logs->indexData($request, $request->validated()))->toResponse($request)->header('Cache-Control', 'private, no-store');
    }

    public function download(Request $request): Response
    {
        ['path' => $path, 'name' => $name, 'type' => $type] = $this->logs->downloadFile($request);
        $headers = ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'];
        if ($type === 'log') {
            return response()->streamDownload(fn () => $this->reader->download($path), $name, $headers + ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        return response()->download($path, $name, $headers + ['Content-Type' => 'application/octet-stream'])->setPrivate();
    }
}
