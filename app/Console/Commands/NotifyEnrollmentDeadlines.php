<?php

namespace App\Console\Commands;

use App\Models\Learning\Enrollment;
use App\Services\Learning\ParticipantNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NotifyEnrollmentDeadlines extends Command
{
    protected $signature = 'participant:notify-deadlines';
    protected $description = 'Create participant notifications for approaching or passed enrollment deadlines';

    public function handle(ParticipantNotificationService $notifications): int
    {
        if (! config('participant.notifications_enabled', true)) return self::SUCCESS;

        $now = now();
        $created = 0;
        Enrollment::query()->with(['course', 'learn'])
            ->where('status', 'active')->whereNotNull('access_ends_at')
            ->where('access_ends_at', '<=', $now->copy()->addDay())
            ->whereDoesntHave('learn', fn ($query) => $query->whereNotNull('finished_at'))
            ->orderBy('id')->chunkById(200, function ($enrollments) use ($now, $notifications, &$created) {
                foreach ($enrollments as $enrollment) {
                    $end = $enrollment->access_ends_at;
                    if ($end->lte($now)) {
                        DB::transaction(function () use ($enrollment, $notifications, &$created) {
                            $locked = Enrollment::query()->lockForUpdate()->find($enrollment->id);
                            if (! $locked || $locked->status !== 'active' || $locked->learn()->whereNotNull('finished_at')->exists()) return;
                            $locked->update(['status' => 'expired']);
                            $notifications->deadline($locked, 'expired');
                            $created++;
                        });
                    } else {
                        $kind = $end->lte($now->copy()->addHour()) ? 'reminder_1h' : 'reminder_24h';
                        $before = DB::table('participant_notifications')->where('participant_id', $enrollment->participant_id)
                            ->where('event_key', 'enrollment:'.$enrollment->id.':'.$kind)->exists();
                        if (! $before) { $notifications->deadline($enrollment, $kind); $created++; }
                    }
                }
            });

        $this->info("Pemeriksaan tenggat selesai; {$created} notifikasi dibuat.");
        return self::SUCCESS;
    }
}
