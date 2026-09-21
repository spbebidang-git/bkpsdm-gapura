<?php

namespace App\Models\Learning;

use App\Models\Course\Course;
use App\Models\Course\Schedule;
use App\Models\Identity\User;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enrollment extends Model
{
    use HasUlids, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'enrolled_at' => 'datetime',
            'access_starts_at' => 'datetime',
            'access_ends_at' => 'datetime',
        ];
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'participant_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function learn(): HasOne
    {
        return $this->hasOne(Learn::class);
    }

    public function isAccessActive(?\DateTimeInterface $at = null): bool
    {
        $at = $at ? \Carbon\Carbon::instance($at) : now();

        if ($this->status !== 'active') {
            return false;
        }
        if ($at->lt($this->access_starts_at)) {
            return false;
        }
        if ($this->access_ends_at !== null && $at->gt($this->access_ends_at)) {
            return false;
        }

        return true;
    }
}
