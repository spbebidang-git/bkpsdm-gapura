<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Symfony\Component\HttpFoundation\Response;

class SecureFileUploadMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        # Cek apakah ada file di request
        if ($request->hasFile('file')) {
            $file = $request->file('file');

            # 1. Cek tumpukan ekstensi
            $originalName = $file->getClientOriginalName();
            $extCount = substr_count($originalName, '.');
            if ($extCount > 1) {
                return response()->json([
                    'error' => 'Tumpukan ekstensi tidak diperbolehkan.'
                ], 422);
            }

            # 2. Cek MIME asli
            $realMime = File::mimeType($file);
            $allowedMimes = [
                'image/jpeg', 
                'image/png', 
                'application/pdf',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                'application/vnd.ms-excel', // .xls
                'text/csv', // .csv
                'application/csv',
                'text/plain', // some csv detected as plain text
                'application/zip' // sometimes xlsx is detected as zip
            ];
            if (!in_array($realMime, $allowedMimes)) {
                return response()->json([
                    'error' => 'Tipe file tidak valid: ' . $realMime
                ], 422);
            }

            # 3. Cek isi file (signature konten berbahaya)
            $content = file_get_contents($file->getRealPath());
            if (preg_match('/<\?(php|=)|eval|shell_exec|base64_decode/i', $content)) {
                return response()->json([
                    'error' => 'Isi file mencurigakan atau mengandung perintah shell/PHP.'
                ], 422);
            }
        }

        return $next($request);
    }
}
