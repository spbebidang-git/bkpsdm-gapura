<?php

namespace App\Models\Learning;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

class ParticipantNotification extends Model
{
    use HasUlids;

    protected $table = 'participant_notifications';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['data' => 'array', 'read_at' => 'datetime'];
    }
}
