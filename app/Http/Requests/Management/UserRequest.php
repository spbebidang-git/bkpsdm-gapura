<?php

namespace App\Http\Requests\Management;

use App\Models\Identity\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('super_admin') ?? false;
    }

    public function rules(): array
    {
        $target = $this->route('user');
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'nip' => ['nullable', 'string', 'max:50', Rule::unique('users', 'nip')->ignore($target?->id)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target?->id)],
            'password' => $target || $this->input('auth_source') === User::AUTH_SOURCE_SSO
                ? ['nullable', 'string', 'min:8', 'confirmed'] : ['required', 'string', 'min:8', 'confirmed'],
            'opd_id' => ['nullable', 'integer', Rule::exists('opds', 'id')->whereNull('deleted_at')->where('is_active', true)],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', Rule::exists('roles', 'name')->where(fn ($query) => $query
                ->where('guard_name', 'web')->where('name', 'administrator'))],
        ];
        if (! $target) $rules['auth_source'] = ['required', Rule::in([User::AUTH_SOURCE_LOCAL, User::AUTH_SOURCE_SSO])];
        if ($target?->usesSsoAuthentication()) {
            unset($rules['name'], $rules['nip'], $rules['email'], $rules['password'], $rules['opd_id']);
        }
        return $rules;
    }

    public function attributes(): array
    {
        return ['name' => 'Nama pengguna'];
    }
}
