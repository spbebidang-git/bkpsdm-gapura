<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'time_from' => ['required', 'date_format:H:i'],
            'time_to' => ['required', 'date_format:H:i', 'after:time_from'],
            'quota' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'date' => ':attribute harus berupa tanggal yang valid.',
            'date_format' => ':attribute harus menggunakan format jam yang valid.',
            'after' => 'Waktu selesai harus setelah waktu mulai.',
            'integer' => ':attribute harus berupa bilangan bulat.',
            'min' => ':attribute minimal :min.',
        ];
    }

    public function attributes(): array
    {
        return ['date' => 'Tanggal', 'time_from' => 'Waktu mulai', 'time_to' => 'Waktu selesai', 'quota' => 'Kuota'];
    }
}
