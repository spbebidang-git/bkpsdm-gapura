<?php

namespace App\Models\Course;

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
}
