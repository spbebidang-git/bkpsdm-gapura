<?php

namespace App\Http\Controllers\Web;

use Illuminate\Http\Request;
use Mews\Captcha\Facades\Captcha;

class CapthaController
{
    public function captcha()
    {
        return response()->json([
            'captcha' => Captcha::src('default')
        ]);
    }
}
