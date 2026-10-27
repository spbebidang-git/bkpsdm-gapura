<?php

namespace App\Http\Requests\Course;

use Illuminate\Foundation\Http\FormRequest;

class QuestionItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string'],
            'options' => ['required', 'array:option1,option2,option3,option4'],
            'options.option1' => ['required', 'string'],
            'options.option2' => ['required', 'string'],
            'options.option3' => ['required', 'string'],
            'options.option4' => ['required', 'string'],
            'correct_answer' => ['required', 'in:1,2,3,4'],
            'score' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return ['description' => 'Pertanyaan'];
    }
}
