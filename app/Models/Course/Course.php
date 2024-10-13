<?php

namespace App\Models\Course;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    use HasFactory;

    public static $directoryImage = 'assets/media/images/courses/';

    protected $guarded = [];

    public function instructor()
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }

    public function activities()
    {
        return $this->hasMany(Activity::class);
    }

    public function thumbnailSrc(): Attribute
    {
        if (!$this->thumbnail) {
            return Attribute::get(fn() => asset('assets/media/images/static/default-class.jpg'));
        }

        return Attribute::get(fn() => asset(self::$directoryImage . $this->thumbnail));
    }

    public function quota(): Attribute
    {
        if ($this->type == 'schedule') {
            return Attribute::get(fn() => 'limited');
        }

        return Attribute::get(fn() => 'unlimited');
    }

    public function totalParticipant(): Attribute
    {
        return Attribute::get(fn() => 'belum');
    }
}
