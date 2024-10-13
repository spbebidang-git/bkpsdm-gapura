<?php

namespace App\Models\Course;

use App\Models\Course\Question\QuestionVariant;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    public $guarded = [];

    public function questionVariant()
    {
        return $this->hasMany(QuestionVariant::class);
    }
}
