<?php

namespace App\Models\Learning;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearnItem extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    public $guarded = [];

    public function learn()
    {
        return $this->belongsTo(Learn::class);
    }

    public function getStatus($participantId)
    {
        $learn = Learn::where('participant_id', $participantId)->first();

        // $learn->
        if ($learn->first()) {
            return $this->status;
        }

        return 0;
    }
}
