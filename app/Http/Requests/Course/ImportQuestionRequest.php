<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;

class ImportQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['content' => ['required', 'mimes:xlsx,xls', 'max:2048']];
    }

    public function attributes(): array
    {
        return ['content' => 'Berkas soal'];
    }
}
