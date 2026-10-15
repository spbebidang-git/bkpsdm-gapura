<?php

namespace App\Models\Learn;

use App\Models\Course\Activity;
use Illuminate\Database\Eloquent\Model;

class ActivityRuntimeState extends Model
{
    public $guarded = [];

    protected $casts = [
        'state' => 'array',
        'score_raw' => 'float',
        'last_accessed_at' => 'datetime',
    ];

    public function learn()
    {
        return $this->belongsTo(Learn::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}
