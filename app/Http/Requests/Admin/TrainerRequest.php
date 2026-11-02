<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TrainerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $request = $this;
        $trainer = $this->route('trainer');

        if ($trainer?->user_id) {
            abort_unless($request->user()?->hasAnyRole(['super_admin', 'administrator']), 403);
        }
        $source = $request->input('source');
        $isUser = $source === 'user';
        $isRegistration = $isUser && ! $trainer;
        $localAccount = $source === 'local';
        $editingLocalAccount = $trainer?->user?->usesLocalAuthentication() ?? false;

        return [
            'source' => ['required', Rule::in($trainer?->user_id ? ['user'] : ($trainer ? ['local'] : ['local', 'user']))],
            'user_id' => $isRegistration ? ['required', 'ulid', Rule::exists('users', 'id')->whereNull('deleted_at')]
                : ['prohibited'],
            'name' => $isRegistration ? ['prohibited'] : ['required', 'string', 'max:255'],
            'jabatan' => $isRegistration ? ['prohibited'] : ['nullable', 'string', 'max:255'],
            'opd_ids' => $isRegistration ? ['nullable', 'array'] : ['required', 'array', 'min:1'],
            'opd_ids.*' => ['required', 'integer', 'distinct', Rule::exists('opds', 'id')->whereNull('deleted_at')->where('is_active', true)],
            'photo' => $isRegistration ? ['prohibited'] : ['nullable', 'image', 'max:2048', 'mimes:png,jpg,jpeg'],
            'email' => $localAccount ? ['required', 'email', 'max:255', Rule::unique('users', 'email')]
                : ($editingLocalAccount ? ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($trainer->user_id)] : ['prohibited']),
            'password' => $localAccount ? ['required', 'string', 'min:8', 'confirmed']
                : ($editingLocalAccount ? ['nullable', 'string', 'min:8', 'confirmed'] : ['prohibited']),
        ];
    }

    public function attributes(): array
    {
        return ['source' => 'Sumber pemateri', 'user_id' => 'Pengguna', 'name' => 'Nama pemateri', 'jabatan' => 'Jabatan', 'photo' => 'Foto pemateri', 'opd_ids' => 'OPD pemateri'];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('opd_ids') && $this->filled('opd_id')) {
            $this->merge(['opd_ids' => [(int) $this->input('opd_id')]]);
        }

    }
}
