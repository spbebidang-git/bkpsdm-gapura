<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use Mews\Captcha\Facades\Captcha;

class CapthaController
{
    public function captcha()
    {
        // dd(Captcha::img('flat'));
        return response()->json([
            'captcha' => Captcha::src('default')
        ]);

        // return response()->make(Captcha::src('default'), 200, ['Content-Type' => 'image/png']);
    }
}
