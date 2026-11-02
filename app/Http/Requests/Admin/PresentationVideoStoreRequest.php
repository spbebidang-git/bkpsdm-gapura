<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PresentationVideoStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string',
            'description' => 'required|string',
            'value' => 'required|numeric',
            'url' => ['required', 'string', 'regex:%^https://(www\.)?youtube\.com/embed/[\w-]+(\?.*)?$%i'],
            'duration' => 'required|numeric|min:0.1',
        ];
    }

    public function messages(): array
    {
        return [
            'url.regex' => 'Tautan video harus berupa tautan embed YouTube (contoh: https://www.youtube.com/embed/VIDEO_ID).',
            'duration.min' => 'Durasi video minimal 0,1 menit (6 detik).',
        ];
    }
}
