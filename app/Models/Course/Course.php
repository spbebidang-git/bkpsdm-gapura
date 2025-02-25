<?php

namespace App\Models\Course;

use App\Models\Learn\Learn;
use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

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

    public function learns()
    {
        return $this->hasMany(Learn::class, 'course_id');
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
            return Attribute::get(fn() => 'Terbatas');
        }

        return Attribute::get(fn() => 'Tidak Terbatas');
    }

    public function totalParticipant(): Attribute
    {
        return Attribute::get(fn() => $this->schedules->sum(function ($schedule) {
            return $schedule->participants()->count();
        }));
    }

    public function totalPassed(): Attribute
    {
        return Attribute::get(fn() => $this->learns()->where('finished_at', '!=', null)->count());
    }

    public function efektifitas(): Attribute
    {
        return Attribute::get(fn() => ($this->total_passed / $this->total_participant) * 100);
    }

    public function getStar(): Attribute
    {
        $totalRating = $this->reviews()->count(); // Total pengguna yang memberikan rating

        $totalStars = $this->reviews()->sum('star');
        if ($totalRating > 0) {
            $averageStar = $totalStars / $totalRating; // Rata-rata star
        } else {
            $averageStar = 0;
        }

        return Attribute::get(fn() => round($averageStar, 2));
    }

    public function getRating()
    {
        $totalRating = $this->reviews()->count(); // Total pengguna yang memberikan rating
        $totalStars = $this->reviews()->sum('star'); // Jumlah total star

        if ($totalRating > 0) {
            $averageStar = $totalStars / $totalRating; // Rata-rata star
        } else {
            $averageStar = 0;
        }

        return [
            'star' => round($averageStar, 2), // Nilai star (dibulatkan 2 desimal)
            'value' => number_format($averageStar, 2), // Value adalah nilai star rata-rata yang sudah diformat
            'total' => $totalRating, // Total jumlah rating
        ];
    }
}
