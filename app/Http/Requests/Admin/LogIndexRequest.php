<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class LogIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['sometimes', 'string', 'max:255'],
            'level' => ['nullable', 'in:debug,info,notice,warning,error,critical,alert,emergency,legacy,unknown'],
            'q' => ['nullable', 'string', 'max:200'],
            'request_id' => ['nullable', 'uuid'],
        ];
    }
}
