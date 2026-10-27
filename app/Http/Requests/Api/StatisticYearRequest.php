<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StatisticYearRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['year' => ['sometimes', 'integer', 'between:1900,2100']];
    }
}
