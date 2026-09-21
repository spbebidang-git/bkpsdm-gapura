<?php

namespace App\Http\Controllers\Web;

use Mews\Captcha\Facades\Captcha;

class CapthaController
{
    public function captcha()
    {
        $payload = Captcha::create('default', true);

        // mews/captcha 3.5 + intervention/image 4: img is DataUri object, not string
        if (isset($payload['img']) && ! is_string($payload['img'])) {
            $payload['img'] = (string) $payload['img'];
        }

        return response()->json($payload);
    }
}
