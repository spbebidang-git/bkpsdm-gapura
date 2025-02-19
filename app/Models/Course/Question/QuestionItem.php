<?php

namespace App\Models\Course\Question;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionItem extends Model
{
    use HasFactory, SoftDeletes;

    public $guarded = [];

    public function questionOptions()
    {
        return $this->hasMany(QuestionOption::class);
    }
}
