<?php

namespace App\Http\Requests\Course;

class UpdateCourseRequest extends CourseRequest
{
    protected function thumbnailRules(): array
    {
        return ['nullable', 'image', 'max:10240', 'mimes:png,jpg'];
    }
}
