<?php

namespace App\Console\Commands;

use App\Models\Learning\Enrollment;
use App\Models\Learning\Learn;
use Illuminate\Console\Command;

class ReconcileParticipantEnrollments extends Command
{
    protected $signature = 'participant:reconcile-enrollments {--apply : Link learning records when exactly one matching enrollment exists}';
    protected $description = 'Audit and safely link legacy learning records to participant enrollments';

    public function handle(): int
    {
        $linked = $ambiguous = $missing = 0;
        Learn::query()->whereNull('enrollment_id')->orderBy('id')->chunkById(200, function ($learns) use (&$linked, &$ambiguous, &$missing) {
            foreach ($learns as $learn) {
                $ids = Enrollment::query()->where('participant_id', $learn->participant_id)
                    ->where('course_id', $learn->course_id)->orderBy('id')->limit(2)->pluck('id');
                if ($ids->count() === 1) {
                    $linked++;
                    if ($this->option('apply')) Learn::query()->whereKey($learn->id)->whereNull('enrollment_id')->update(['enrollment_id' => $ids->first()]);
                } elseif ($ids->isEmpty()) {
                    $missing++;
                    $this->warn("Tidak ada enrollment: learn {$learn->id}, pengguna {$learn->participant_id}, kelas {$learn->course_id}");
                } else {
                    $ambiguous++;
                    $this->warn("Enrollment ambigu: learn {$learn->id}, pengguna {$learn->participant_id}, kelas {$learn->course_id}");
                }
            }
        });

        $mode = $this->option('apply') ? 'ditautkan' : 'siap ditautkan (dry run)';
        $this->info("{$linked} {$mode}; {$ambiguous} ambigu; {$missing} tanpa enrollment.");
        return $ambiguous || $missing ? self::FAILURE : self::SUCCESS;
    }
}
