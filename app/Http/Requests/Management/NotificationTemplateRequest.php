<?php

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;

class NotificationTemplateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $keys = array_keys(config('notification_messages', []));
        $rules = ['templates' => ['required', 'array:'.implode(',', $keys)]];
        foreach ($keys as $key) {
            $rules["templates.{$key}.title"] = ['required', 'string', 'max:160'];
            $rules["templates.{$key}.message"] = ['required', 'string', 'max:2000'];
        }
        return $rules;
    }
}
