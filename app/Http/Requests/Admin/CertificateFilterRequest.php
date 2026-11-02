<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CertificateFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        if ($this->routeIs('admin.certificate.index') && (! $this->ajax() || $this->header('X-Inertia'))) {
            return [];
        }

        return [
            'status_filter' => 'nullable|in:menunggu,dikirim,tersedia,tidak_tersedia',
            'course_filter' => 'nullable|string',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => ['nullable', 'date_format:Y-m-d', ...($this->filled('date_from') ? ['after_or_equal:date_from'] : [])],
            'q' => 'nullable|string|max:255',
        ];
    }
}
