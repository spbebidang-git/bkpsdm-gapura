<?php

namespace App\Models\Course\Activity\Assessment;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuestionItem extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public $guarded = [];

    public function questionOptions()
    {
        return $this->hasMany(QuestionOption::class);
    }
}
