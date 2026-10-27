<?php

namespace App\Models\Course\Questionnaire;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionnaireAnswer extends Model
{
    use HasFactory;

    protected $table = 'questionnaire_answers';

    public $guarded = [];

    public function response()
    {
        return $this->belongsTo(QuestionnaireResponse::class, 'response_id');
    }

    public function question()
    {
        return $this->belongsTo(QuestionnaireQuestion::class, 'question_id');
    }

    public function choice()
    {
        return $this->belongsTo(QuestionnaireChoice::class, 'choice_id');
    }
}
