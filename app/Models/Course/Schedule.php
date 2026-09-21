<?php

namespace App\Models\Course;

use App\Models\Learning\Enrollment;
use App\Models\Identity\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    // ponytail: no datetime cast on time_* — it stamped today's date onto time-only columns
    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public $guarded = [];

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'schedule_user', 'schedule_id', 'participant_id');
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function date(): Attribute
    {
        return Attribute::get(function () {
            if (($this->course?->getAttributes()['access_mode'] ?? null) === 'open') {
                return 'Tidak Terjadwal';
            }

            $raw = $this->getRawOriginal('date');

            return $raw ? Carbon::parse($raw)->format('Y-m-d') : null;
        });
    }

    public function time(): Attribute
    {
        return Attribute::get(function () {
            if ($this->course?->access_mode === 'open') {
                return 'Tidak Terjadwal';
            }

            $from = $this->getRawOriginal('time_from');
            $to = $this->getRawOriginal('time_to');
            if (!$from && !$to) {
                return null;
            }

            $from = $from ? Carbon::parse($from)->format('H:i') : '';
            $to = $to ? Carbon::parse($to)->format('H:i') : '';

            return "{$from}-{$to}";
        });
    }

    public function quota(): Attribute
    {
        return Attribute::get(function ($value) {
            if ($this->course?->access_mode === 'open') {
                return 'Tidak Terbatas';
            }

            return $value;
        });
    }
}
