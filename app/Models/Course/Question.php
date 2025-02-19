<?php

namespace App\Models\Course;

use App\Models\Course\Question\QuestionVariant;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    public $guarded = [];

    public function questionVariant()
    {
        return $this->hasMany(QuestionVariant::class);
    }
}
