<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeStatusStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'unique:employee_status,title'],
        ];
    }

    public function attributes(): array
    {
        return ['title' => 'Nama status pegawai'];
    }
}
