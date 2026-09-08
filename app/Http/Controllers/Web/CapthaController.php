<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\JsonResponse;

class CapthaController
{
    public function captcha(): JsonResponse
    {
        $characters = config('captcha.characters');
        $length = (int) config('captcha.default.length', 5);
        $answer = '';

        for ($index = 0; $index < $length; $index++) {
            $answer .= $characters[random_int(0, count($characters) - 1)];
        }

        $key = bin2hex(random_bytes(16));
        session([
            'app_captcha.answer' => $answer,
            'app_captcha.key' => $key,
        ]);

        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="150" height="50" viewBox="0 0 150 50"><rect width="100%%" height="100%%" fill="#f8fafc"/><path d="M0 37 L150 13 M0 15 L150 35" stroke="#cbd5e1" stroke-width="1"/><text x="75" y="34" text-anchor="middle" font-family="monospace" font-size="28" font-weight="700" letter-spacing="5" fill="#018083">%s</text></svg>',
            $answer,
        );

        return response()->json([
            'img' => 'data:image/svg+xml;base64,' . base64_encode($svg),
            'key' => $key,
        ]);
    }
}
