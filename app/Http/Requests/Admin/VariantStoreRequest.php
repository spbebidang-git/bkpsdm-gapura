<?php

namespace App\Http\Requests\Admin;

use App\Services\Course\Activity\Question\VariantService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VariantStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        app(VariantService::class)->assertActivity($this->route('course'), $this->route('activityId'));
        $variant = $this->route('variant');
        if ($variant) {
            abort_unless($variant->question_id === app(VariantService::class)->getQuestionId($this->route('activityId')), 404);
        }

        return true;
    }

    public function rules(): array
    {
        $activityId = $this->route('activityId');

        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('question_variants', 'code')->where('question_id', app(VariantService::class)->getQuestionId($activityId))],
            'variant' => 'required|integer|min:1',
            'mix' => 'required|boolean',
        ];
    }
}
