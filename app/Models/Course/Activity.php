<?php

namespace App\Models\Course;

use App\Models\Learn\Learn;
use App\Models\Learn\LearnItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Activity extends Model
{
    use HasFactory, SoftDeletes;

    public $guarded = [];

    public function question()
    {
        return $this->belongsTo(Question::class, 'activity_id');
    }

    public function learnItem()
    {
        return $this->hasOne(LearnItem::class, 'activity_id');
    }

    public function h5p()
    {
        return $this->hasOne(H5p::class, 'activity_id');
    }
}
