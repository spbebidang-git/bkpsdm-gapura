<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ParticipantUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $participant = $this->route('participant');
        if ($participant) {
            abort_unless($participant->usesLocalAuthentication() && $participant->hasRole('participant'), 404);
        }

        return true;
    }

    public function rules(): array
    {
        $participant = $this->route('participant');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($participant->id)],
            'nip' => ['nullable', 'string', 'max:255', Rule::unique('users', 'nip')->ignore($participant->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'employee_status_id' => ['nullable', 'string', Rule::exists('employee_status', 'id')->whereNull('deleted_at')],
            'opd_id' => ['nullable', 'integer', Rule::exists('opds', 'id')->whereNull('deleted_at')->where('is_active', true)],
            'jabatan' => ['nullable', 'string', 'max:255'],
        ];
    }
}
