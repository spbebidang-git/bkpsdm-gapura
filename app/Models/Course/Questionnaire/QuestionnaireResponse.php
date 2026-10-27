<?php

namespace App\Models\Course\Questionnaire;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionnaireResponse extends Model
{
    use HasFactory;

    protected $table = 'questionnaire_responses';

    public $guarded = [];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function questionnaire()
    {
        return $this->belongsTo(\App\Models\Course\Questionnaire::class, 'questionnaire_id');
    }

    public function participant()
    {
        return $this->belongsTo(User::class, 'participant_id');
    }

    public function answers()
    {
        return $this->hasMany(QuestionnaireAnswer::class, 'response_id');
    }
}
