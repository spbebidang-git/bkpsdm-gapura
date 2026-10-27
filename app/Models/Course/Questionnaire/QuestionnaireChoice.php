<?php

namespace App\Models\Course\Questionnaire;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionnaireChoice extends Model
{
    use HasFactory;

    protected $table = 'questionnaire_choices';

    public $guarded = [];

    public function question()
    {
        return $this->belongsTo(QuestionnaireQuestion::class, 'question_id');
    }
}
