<?php

namespace App\Models\Course\Questionnaire;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionnaireQuestion extends Model
{
    use HasFactory;

    protected $table = 'questionnaire_questions';

    public $guarded = [];

    /**
     * Tipe pertanyaan (nama mengikuti mod_questionnaire) -> label Indonesia.
     * `section` bukan pertanyaan: hanya teks pengantar antar bagian.
     */
    const TYPES = [
        'yesno'   => 'Ya/Tidak',
        'text'    => 'Isian singkat',
        'essay'   => 'Esai',
        'radio'   => 'Pilihan ganda',
        'check'   => 'Pilihan jamak',
        'drop'    => 'Dropdown',
        'rate'    => 'Skala penilaian',
        'date'    => 'Tanggal',
        'numeric' => 'Angka',
        'section' => 'Teks bagian',
    ];

    /** Tipe yang jawabannya berupa pilihan (butuh baris di questionnaire_choices). */
    const CHOICE_TYPES = ['radio', 'check', 'drop', 'rate'];

    public function questionnaire()
    {
        return $this->belongsTo(\App\Models\Course\Questionnaire::class, 'questionnaire_id');
    }

    public function choices()
    {
        return $this->hasMany(QuestionnaireChoice::class, 'question_id')
            ->orderBy('position')
            ->orderBy('id');
    }

    public function answers()
    {
        return $this->hasMany(QuestionnaireAnswer::class, 'question_id');
    }
}
