<?php

namespace App\Models\Course\Question;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class QuestionVariant extends Model
{
    use HasFactory;

    public $guarded = [];

    public function questionItems()
    {
        return $this->hasMany(QuestionItem::class);
    }
}
