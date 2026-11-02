<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ParticipantStoreRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'nip' => ['nullable', 'string', 'max:255', 'unique:users,nip'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'employee_status_id' => ['nullable', 'string', Rule::exists('employee_status', 'id')->whereNull('deleted_at')],
            'opd_id' => ['nullable', 'integer', Rule::exists('opds', 'id')->whereNull('deleted_at')->where('is_active', true)],
            'jabatan' => ['nullable', 'string', 'max:255'],
        ];
    }
}
