<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;

class SettingRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'site_title' => ['required', 'string'],
            'site_description' => ['required', 'string'],
            'site_logo' => ['nullable', 'image', 'mimes:png,jpg', 'max:10240'],
            'home_banner' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:15360'],
        ];
    }
}
