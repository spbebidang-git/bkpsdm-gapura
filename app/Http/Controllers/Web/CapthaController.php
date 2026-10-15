<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class CapthaController
{
    public function captcha(): JsonResponse
    {
        $characters = (array) config('captcha.characters', ['2', '3', '4', '5', '6', '7', '8', '9']);
        $answer = collect(range(1, 5))
            ->map(fn () => $characters[random_int(0, count($characters) - 1)])
            ->implode('');
        $key = Str::random(32);

        session()->put('app_captcha', [
            'key' => $key,
            'answer' => $answer,
        ]);

        $width = 180;
        $height = 56;
        $noise = collect(range(1, 10))->map(function () use ($width, $height) {
            $x1 = random_int(0, $width);
            $y1 = random_int(0, $height);
            $x2 = random_int(0, $width);
            $y2 = random_int(0, $height);

            return "<line x1=\"{$x1}\" y1=\"{$y1}\" x2=\"{$x2}\" y2=\"{$y2}\" stroke=\"#018083\" stroke-opacity=\".18\" stroke-width=\"2\"/>";
        })->implode('');

        $dots = collect(range(1, 24))->map(function () use ($width, $height) {
            $cx = random_int(4, $width - 4);
            $cy = random_int(4, $height - 4);
            $radius = random_int(1, 2);

            return "<circle cx=\"{$cx}\" cy=\"{$cy}\" r=\"{$radius}\" fill=\"#018083\" fill-opacity=\".2\"/>";
        })->implode('');

        $charactersSvg = collect(str_split($answer))->values()->map(function (string $character, int $index) {
            $x = 32 + ($index * 30);
            $y = random_int(35, 43);
            $rotation = random_int(-14, 14);

            return "<text x=\"{$x}\" y=\"{$y}\" transform=\"rotate({$rotation} {$x} {$y})\" text-anchor=\"middle\" font-family=\"Arial,sans-serif\" font-size=\"25\" font-weight=\"700\" fill=\"#015f61\">{$character}</text>";
        })->implode('');

        $svg = "<svg xmlns=\"http://www.w3.org/2000/svg\" width=\"{$width}\" height=\"{$height}\" viewBox=\"0 0 {$width} {$height}\"><rect width=\"100%\" height=\"100%\" rx=\"12\" fill=\"#eef8f6\"/>{$noise}{$dots}{$charactersSvg}</svg>";

        return response()->json([
            'key' => $key,
            'img' => 'data:image/svg+xml;base64,' . base64_encode($svg),
        ]);
    }
}
