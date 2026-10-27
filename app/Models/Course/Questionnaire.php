<?php

namespace App\Models\Course;

use App\Models\Course\Questionnaire\QuestionnaireQuestion;
use App\Models\Course\Questionnaire\QuestionnaireResponse;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Questionnaire extends Model
{
    use HasFactory, SoftDeletes;

    public $guarded = [];

    public function activity()
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function questions()
    {
        return $this->hasMany(QuestionnaireQuestion::class, 'questionnaire_id')
            ->orderBy('position')
            ->orderBy('id');
    }

    public function responses()
    {
        return $this->hasMany(QuestionnaireResponse::class, 'questionnaire_id');
    }
}
