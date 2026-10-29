<?php

namespace App\Models\Course;

use App\Models\Course\Activity\Activity;
use App\Models\Identity\EmployeeStatus;
use App\Models\Identity\Opd;
use App\Models\Learning\Enrollment;
use App\Models\Learning\Learn;
use App\Models\Concerns\TracksModifier;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, HasUlids, SoftDeletes, TracksModifier;

    const EMPLOYEE_STATUS = [
        'PNS',
        'CPNS',
        'PPPK',
        'THL'
    ];

    public static $directoryImage = 'assets/media/images/courses/';

    protected $guarded = [];

    protected $attributes = ['visibility' => 'private', 'status' => 'draft'];

    public function opds()
    {
        return $this->belongsToMany(Opd::class, 'course_opd');
    }

    public function audienceData(): array
    {
        $opds = $this->visibility === 'public' ? collect() : $this->opds;
        return [
            'visibility' => $this->visibility,
            'opd_ids' => $opds->pluck('id')->all(),
            'opds' => $opds->map(fn ($opd) => ['id' => $opd->id, 'name' => $opd->name])->values()->all(),
            'audience_scope' => $this->visibility === 'public' ? 'all' : 'opd',
            'opd_name' => $opds->pluck('name')->join(', ') ?: null,
        ];
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function opd()
    {
        return $this->belongsTo(Opd::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
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

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function employeeStatus()
    {
        return $this->belongsToMany(EmployeeStatus::class, 'course_employee_status');
    }

    public function trainers()
    {
        return $this->belongsToMany(Trainer::class, 'course_trainer');
    }

    public function thumbnailSrc(): Attribute
    {
        if (!$this->thumbnail) {
            return Attribute::get(fn() => asset('assets/media/images/static/default-class.jpg'));
        }

        if ($this->thumbnail === 'default-class.jpg') {
            return Attribute::get(fn() => asset('assets/media/images/static/default-class.jpg'));
        }

        return Attribute::get(fn() => asset(self::$directoryImage . $this->thumbnail));
    }

    /** BC frontend/API: schedule|unschedule dari access_mode. */
    protected function type(): Attribute
    {
        return Attribute::get(fn () => ($this->attributes['access_mode'] ?? null) === 'scheduled'
            ? 'schedule'
            : 'unschedule');
    }

    /** BC: expired = default_access_hours. */
    protected function expired(): Attribute
    {
        return Attribute::get(fn () => $this->attributes['default_access_hours'] ?? null);
    }

    public static function mapTypeToAccessMode(?string $type): string
    {
        return $type === 'schedule' ? 'scheduled' : 'open';
    }

    public function quota(): Attribute
    {
        if (($this->attributes['access_mode'] ?? null) === 'scheduled') {
            return Attribute::get(fn () => 'Terbatas');
        }

        return Attribute::get(fn () => 'Tidak Terbatas');
    }

    public function totalParticipant(): Attribute
    {
        return Attribute::get(fn() => $this->enrollments()->distinct()->count('participant_id'));
    }

    public function totalPassed(): Attribute
    {
        return Attribute::get(fn() => $this->learns()->where('finished_at', '!=', null)->count());
    }

    public function efektifitas(): Attribute
    {
        return Attribute::get(function () {
            $participants = $this->total_participant;

            return $participants > 0 ? round(($this->total_passed / $participants) * 100, 1) : 0;
        });
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
