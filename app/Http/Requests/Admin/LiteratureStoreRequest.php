<?php

namespace App\Http\Requests\Admin;

use App\Services\Course\Activity\LiteratureUploadAuditService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class LiteratureStoreRequest extends FormRequest
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
            'duration' => 'required|numeric|min:0.1',
            'content' => 'required|mimes:pdf|max:5120',
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
        app(LiteratureUploadAuditService::class)->received($this, 'store');
    }

    protected function failedValidation(Validator $validator)
    {
        app(LiteratureUploadAuditService::class)->validationFailed($this, 'store', $validator->errors()->toArray());
        parent::failedValidation($validator);
    }
}
