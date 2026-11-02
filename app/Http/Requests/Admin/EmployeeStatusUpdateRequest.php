<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeStatusUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employeeStatus = $this->route('employeeStatus');

        return [
            'title' => ['required', 'string', 'max:255', Rule::unique('employee_status', 'title')->ignore($employeeStatus->id)],
        ];
    }

    public function attributes(): array
    {
        return ['title' => 'Nama status pegawai'];
    }
}
