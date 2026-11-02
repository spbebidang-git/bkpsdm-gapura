<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ImportLearnStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'course_id' => 'required|exists:courses,id',
            'finished_at' => 'required|date',
            'file' => 'required|file|max:5120',
            'create_missing_users' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'course_id.required' => 'Pilih kelas terlebih dahulu.',
            'file.required' => 'Berkas Excel wajib diunggah.',
        ];
    }
}
