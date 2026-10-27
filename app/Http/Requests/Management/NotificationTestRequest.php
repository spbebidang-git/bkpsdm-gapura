<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class NotificationTestRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'participant_id' => ['required', 'string', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'course_id' => ['required', 'string', Rule::exists('courses', 'id')->where('status', 'publish')->whereNull('deleted_at')],
            'event_key' => ['required', Rule::in(array_keys(config('notification_messages', [])))],
        ];
    }
}
