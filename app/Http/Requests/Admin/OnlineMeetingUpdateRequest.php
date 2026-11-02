<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class OnlineMeetingUpdateRequest extends FormRequest
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
            'url' => 'required|string',
            'duration' => 'required|numeric|min:0.1',
        ];
    }

    public function messages(): array
    {
        return [
            'duration.min' => 'Durasi pertemuan minimal 0,1 menit (6 detik).',
        ];
    }
}
