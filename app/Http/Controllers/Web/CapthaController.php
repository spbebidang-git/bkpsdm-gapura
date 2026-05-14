<?php

namespace App\Http\Controllers\Web;

use Mews\Captcha\Facades\Captcha;

class CapthaController
{
    public function captcha()
    {
        return response()->json(Captcha::create('default', true));
    }
}
