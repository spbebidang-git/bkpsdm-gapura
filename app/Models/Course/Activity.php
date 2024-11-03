<?php

namespace App\Models\Course;

use App\Models\Learn\Learn;
use App\Models\Learn\LearnItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    use HasFactory;

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
