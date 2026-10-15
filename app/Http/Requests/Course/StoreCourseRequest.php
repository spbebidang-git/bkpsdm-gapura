<?php

namespace App\Http\Requests\Course;

class StoreCourseRequest extends CourseRequest
{
    protected function thumbnailRules(): array
    {
        return ['required', 'image', 'max:10240', 'mimes:png,jpg'];
    }
}
