<?php

namespace App\Models\Learn;

use App\Models\Course\Activity;
use App\Models\Course\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Learn extends Model
{
    use HasFactory;

    public $guarded = [];

    protected $casts = [
        'finished_at' => 'datetime',
    ];


    public function learnItems()
    {
        return $this->hasMany(LearnItem::class);
    }

    public function runtimeStates()
    {
        return $this->hasMany(ActivityRuntimeState::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function participant()
    {
        return $this->belongsTo(User::class, 'participant_id');
    }

    public function progress(): Attribute
    {
        $doneActivity = $this->learnItems()->where('status', 'done')->count();
        $totalActivity = $this->course ? $this->course->activities->count() : 0;

        if ($totalActivity == 0) {
            return Attribute::get(fn() => 0 . '%');
        }

        return Attribute::get(fn() => round(($doneActivity / $totalActivity) * 100, 2) . '%');
    }

    public function isAllowRating()
    {
        $activityCount = Activity::where('course_id', $this->course_id)->count();
        $learnItemsCount = $this->learnItems()->count();

        if ($activityCount == $learnItemsCount) {
            return true;
        }

        return false;
    }
}
