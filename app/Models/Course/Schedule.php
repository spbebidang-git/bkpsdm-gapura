<?php

namespace App\Models\Course;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $casts = [
        'date' => 'date',              // otomatis jadi Carbon date
        'time_from' => 'datetime:H:i', // otomatis jadi Carbon datetime (jam menit)
    ];

    public $guarded = [];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, null, 'schedule_id', 'participant_id');
    }

    public function date(): Attribute
    {
        if ($this->course->type == 'unschedule') {
            return  Attribute::get(fn() => 'Tidak Terjadwal');
        }

        return Attribute::get(fn($row) => $row);
    }

    public function time(): Attribute
    {
        if ($this->course->type == 'unschedule') {
            return  Attribute::get(fn() => 'Tidak Terjadwal');
        }

        return Attribute::get(fn() => $this->time_from . '-' .  $this->time_to);
    }

    public function quota(): Attribute
    {
        if ($this->course->type == 'unschedule') {
            return  Attribute::get(fn() => 'Tidak Terbatas');
        }

        return Attribute::get(fn($row) => $row);
    }
}
