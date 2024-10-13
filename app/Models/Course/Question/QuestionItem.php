<?php

namespace App\Models\Course\Question;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class QuestionItem extends Model
{
    use HasFactory;

    public $guarded = [];

    public function questionOptions()
    {
        return $this->hasMany(QuestionOption::class);
    }
}
