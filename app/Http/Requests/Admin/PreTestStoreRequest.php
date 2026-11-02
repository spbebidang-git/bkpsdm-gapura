<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class PreTestStoreRequest extends FormRequest
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
            'instruction' => 'required|string',
            'value' => 'required|numeric',
            'timer' => 'required|numeric|min:1',
            'min_score' => 'required|numeric',
        ];
    }
}
