<?php

namespace App\Console\Commands;

use App\Models\Learning\ParticipantNotification;
use Illuminate\Console\Command;

class PruneParticipantNotifications extends Command
{
    protected $signature = 'participant:prune-notifications';
    protected $description = 'Remove participant notifications older than 180 days';

    public function handle(): int
    {
        $cutoff = now()->subDays(180);
        do {
            $ids = ParticipantNotification::query()->where('created_at', '<', $cutoff)->orderBy('id')->limit(500)->pluck('id');
            if ($ids->isNotEmpty()) ParticipantNotification::query()->whereIn('id', $ids)->delete();
        } while ($ids->count() === 500);

        return self::SUCCESS;
    }
}
