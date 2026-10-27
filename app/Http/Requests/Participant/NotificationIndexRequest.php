<?php

namespace App\Http\Requests\Participant;

use Illuminate\Foundation\Http\FormRequest;

class NotificationIndexRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['filter' => ['nullable', 'in:all,unread']];
    }
}
