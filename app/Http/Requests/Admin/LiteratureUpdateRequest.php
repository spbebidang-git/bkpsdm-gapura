<?php

namespace App\Http\Requests\Admin;

use App\Services\Course\Activity\LiteratureUploadAuditService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class LiteratureUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required',
            'sub_title' => 'required',
            'value' => 'required|numeric',
            'content' => 'nullable|mimes:pdf|max:5120',
            'duration' => 'required|numeric|min:0.1',
        ];
    }

    public function messages(): array
    {
        return [
            'duration.min' => 'Durasi membaca minimal 0,1 menit (6 detik).',
        ];
    }

    protected function prepareForValidation(): void
    {
        app(LiteratureUploadAuditService::class)->received($this, 'update');
    }

    protected function failedValidation(Validator $validator)
    {
        app(LiteratureUploadAuditService::class)->validationFailed($this, 'update', $validator->errors()->toArray());
        parent::failedValidation($validator);
    }
}
