<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LocalLoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'captcha' => ['required', 'captcha'],
        ];
    }

    public function messages(): array
    {
        return [
            'captcha.required' => 'Kode keamanan wajib diisi.',
            'captcha.captcha' => 'Kode keamanan tidak sesuai. Silakan coba lagi.',
        ];
    }
}
