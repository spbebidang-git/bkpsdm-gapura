<?php

namespace App\Models\Learn;

use App\Models\Course\Course;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Learn extends Model
{
    use HasFactory;

    public $guarded = [];

    public function learnItems()
    {
        return $this->hasMany(LearnItem::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function progress(): Attribute
    {
        $doneActivity = $this->learnItems()->where('status', 'done')->count();
        $totalActivity = $this->course->activities->count();

        if ($totalActivity == 0) {
            return Attribute::get(fn() => 0 . '%');
        }

        return Attribute::get(fn() => round(($doneActivity / $totalActivity) * 100, 2) . '%');
    }
}
