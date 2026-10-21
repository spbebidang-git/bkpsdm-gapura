<?php

namespace App\Models\Course\Activity;

use App\Models\Course\Activity\Assessment\Question;
use App\Models\Learning\LearnItem;
use App\Models\Concerns\TracksModifier;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use HasFactory, HasUlids, SoftDeletes, TracksModifier;

    public $guarded = [];

    public function question()
    {
        return $this->belongsTo(Question::class, 'activity_id');
    }

    public function learnItem()
    {
        return $this->hasOne(LearnItem::class, 'activity_id');
    }
}
