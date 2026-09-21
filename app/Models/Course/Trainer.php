<?php

namespace App\Models\Course;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trainer extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public static $directoryImage = 'assets/media/images/trainers/';

    protected $guarded = [];

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_trainer');
    }

    public function photoSrc(): Attribute
    {
        if (! $this->photo) {
            return Attribute::get(fn () => asset('assets/media/images/static/default-class.jpg'));
        }

        return Attribute::get(fn () => asset(self::$directoryImage . $this->photo));
    }
}
