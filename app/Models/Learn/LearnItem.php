<?php

namespace App\Models\Learn;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class LearnItem extends Model
{
    use HasFactory;

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
